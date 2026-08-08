<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifica la firma del ID token de Firebase.
 *
 * Responsabilidad única: leer el header Authorization: Bearer <token>,
 * verificar la firma contra las claves públicas de Google, y poner los
 * claims verificados (firebase_uid, firebase_email, firebase_name) en
 * los atributos del request.
 *
 * No resuelve ni crea el usuario local. Eso lo hace RequerirUsuarioLocal
 * o el propio controller (en el caso de GET /profile).
 */
class VerificarTokenFirebase
{
    private const CACHE_KEY      = 'firebase_public_keys';
    private const LOCK_KEY       = 'firebase_keys_fetch_lock';
    private const MAX_TTL        = 3600;
    private const LOCK_SECONDS   = 10;
    private const BLOCK_SECONDS  = 5;

    public function handle(Request $request, Closure $next): mixed
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return $this->noAutorizado('Token de autorización ausente.');
        }

        try {
            $payload = $this->decodificarToken($bearer);
        } catch (\Throwable $e) {
            Log::debug('VerificarTokenFirebase: token rechazado — ' . $e->getMessage());
            return $this->noAutorizado('Token inválido.');
        }

        // Validar issuer y audience
        $projectId = config('firebase.project_id');
        $issEsperado = "https://securetoken.google.com/{$projectId}";

        if (($payload->iss ?? '') !== $issEsperado) {
            return $this->noAutorizado('Issuer inválido.');
        }

        if (($payload->aud ?? '') !== $projectId) {
            return $this->noAutorizado('Audience inválido.');
        }

        // Inyectar claims verificados en atributos del request
        $request->attributes->set('firebase_uid',   $payload->sub ?? $payload->uid ?? '');
        $request->attributes->set('firebase_email', $payload->email ?? '');
        $request->attributes->set('firebase_name',  $payload->name ?? '');

        return $next($request);
    }

    /**
     * Decodifica y verifica el token JWT.
     * Si el kid no está en el bundle cacheado, invalida el caché y reintenta una vez.
     */
    private function decodificarToken(string $token): object
    {
        // Extraer kid del header sin verificar (no trusted, solo para lookup de clave)
        $kid = $this->extraerKidDelHeader($token);

        $claves = $this->obtenerClavesPublicas();

        // Si el kid no está en el bundle cacheado, refrescar una sola vez
        if ($kid !== null && !array_key_exists($kid, $claves)) {
            Cache::forget(self::CACHE_KEY);
            $claves = $this->obtenerClavesPublicas();
            // Si sigue sin estar, JWT::decode fallará y lanzará excepción → 401
        }

        return JWT::decode($token, $claves);
    }

    /**
     * Obtiene las claves públicas de Google, usando caché en base de datos.
     *
     * Patrón check-lock-check para evitar el thundering herd:
     * solo una invocación descarga las claves cuando el caché expira;
     * las demás esperan hasta BLOCK_SECONDS y luego leen el valor recién cacheado.
     *
     * @return array<string, Key>
     */
    private function obtenerClavesPublicas(): array
    {
        // Camino rápido: hit de caché
        $cacheado = Cache::get(self::CACHE_KEY);
        if ($cacheado !== null) {
            return $this->construirKeySet($cacheado);
        }

        // Camino lento: el caché expiró — tomar el lock distribuido
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

        try {
            $lock->block(self::BLOCK_SECONDS);

            // Double-check: otra invocación puede haber llenado el caché mientras esperábamos
            $cacheado = Cache::get(self::CACHE_KEY);
            if ($cacheado !== null) {
                return $this->construirKeySet($cacheado);
            }

            // Somos los únicos descargando ahora
            return $this->descargarYCachearClaves();

        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // No pudimos adquirir el lock en BLOCK_SECONDS — descargamos directamente
            // como fallback para no bloquear al usuario.
            Log::warning('VerificarTokenFirebase: timeout esperando lock de claves. Descargando directamente.');
            return $this->descargarYCachearClaves();
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Descarga las claves de Google, las cachea con TTL del header Cache-Control
     * (máximo MAX_TTL segundos) y devuelve el KeySet.
     *
     * @return array<string, Key>
     */
    private function descargarYCachearClaves(): array
    {
        $response = Http::timeout(5)
            ->get(config('firebase.keys_url'));

        $response->throw(); // Lanza HttpClientException si falla

        // Parsear TTL desde Cache-Control: max-age=N
        $ttl = self::MAX_TTL;
        $cacheControl = $response->header('Cache-Control') ?? '';
        if (preg_match('/max-age=(\d+)/i', $cacheControl, $matches)) {
            $ttl = min((int)$matches[1], self::MAX_TTL);
        }

        $certs = $response->json(); // ["kid" => "-----BEGIN CERTIFICATE-----\n..."]

        Cache::put(self::CACHE_KEY, $certs, $ttl);

        return $this->construirKeySet($certs);
    }

    /**
     * Convierte el array de certificados X.509 en un KeySet de firebase/php-jwt.
     *
     * @param  array<string, string> $certs  kid => PEM cert string
     * @return array<string, Key>
     */
    private function construirKeySet(array $certs): array
    {
        $keys = [];
        foreach ($certs as $kid => $certPem) {
            // openssl_x509_read extrae la clave pública del certificado X.509
            $certRes = openssl_x509_read($certPem);
            if ($certRes === false) {
                continue; // Ignorar certificados mal formados
            }
            $pubKeyRes = openssl_pkey_get_public($certRes);
            if ($pubKeyRes === false) {
                continue;
            }
            $keys[$kid] = new Key($pubKeyRes, 'RS256');
        }
        return $keys;
    }

    /**
     * Lee el "kid" del header del JWT sin verificar la firma.
     * El valor no es de confianza para nada más allá del lookup de clave.
     */
    private function extraerKidDelHeader(string $token): ?string
    {
        $partes = explode('.', $token);
        if (count($partes) < 2) {
            return null;
        }
        $header = json_decode(base64_decode(strtr($partes[0], '-_', '+/')), true);
        return $header['kid'] ?? null;
    }

    private function noAutorizado(string $mensaje): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => $mensaje], 401);
    }
}
