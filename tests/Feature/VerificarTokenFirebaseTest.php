<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Wishlist;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerificarTokenFirebaseTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-1';
    private string $projectId = 'el-cartucho-test-project';

    protected function setUp(): void
    {
        parent::setUp();

        $openSslConfig = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        if (file_exists('C:\lenguajes\php\extras\ssl\openssl.cnf')) {
            $openSslConfig['config'] = 'C:\lenguajes\php\extras\ssl\openssl.cnf';
        }

        // Generar par de claves y certificado X.509 efímero para tests
        $dn = ["countryName" => "AR", "organizationName" => "Test", "commonName" => "Test"];
        $res = openssl_pkey_new($openSslConfig);
        openssl_pkey_export($res, $keyOut, null, $openSslConfig);
        $this->privateKeyPem = $keyOut;

        $csr = openssl_csr_new($dn, $res, $openSslConfig);
        $cert = openssl_csr_sign($csr, null, $res, 365, $openSslConfig);
        openssl_x509_export($cert, $certOut);
        $this->certPem = $certOut;

        $this->mockGoogleKeys();
    }

    private function mockGoogleKeys(?array $keysMap = null): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        $keys = $keysMap ?? [$this->kid => $this->certPem];
        Http::fake([
            'https://www.googleapis.com/*' => Http::response($keys, 200, ['Cache-Control' => 'max-age=3600']),
        ]);
    }

    private function crearTokenValido(string $uid, string $email = 'test@example.com', string $name = 'Test User', ?int $exp = null, ?string $kid = null): string
    {
        $payload = [
            'iss'   => "https://securetoken.google.com/{$this->projectId}",
            'aud'   => $this->projectId,
            'sub'   => $uid,
            'email' => $email,
            'name'  => $name,
            'iat'   => time() - 10,
            'exp'   => $exp ?? (time() + 3600),
        ];

        return JWT::encode($payload, $this->privateKeyPem, 'RS256', $kid ?? $this->kid);
    }

    public function test_request_sin_header_authorization_devuelve_401(): void
    {
        $response = $this->getJson('/ed/profile');
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token de autorización ausente.']);
    }

    public function test_token_mal_formado_devuelve_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token-mal-formado-123')
            ->getJson('/ed/profile');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token inválido.']);
    }

    public function test_token_con_firma_invalida_devuelve_401(): void
    {
        $openSslConfig = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        if (file_exists('C:\lenguajes\php\extras\ssl\openssl.cnf')) {
            $openSslConfig['config'] = 'C:\lenguajes\php\extras\ssl\openssl.cnf';
        }

        // Generar otra clave privada no coincidente con el cert
        $resOther = openssl_pkey_new($openSslConfig);
        openssl_pkey_export($resOther, $otherPrivateKey, null, $openSslConfig);

        $payload = [
            'iss' => "https://securetoken.google.com/{$this->projectId}",
            'aud' => $this->projectId,
            'sub' => 'uid-123',
            'exp' => time() + 3600,
        ];
        $tokenInvalido = JWT::encode($payload, $otherPrivateKey, 'RS256', $this->kid);

        $response = $this->withHeader('Authorization', "Bearer {$tokenInvalido}")
            ->getJson('/ed/profile');

        $response->assertStatus(401);
    }

    public function test_token_expirado_devuelve_401(): void
    {
        $tokenExpirado = $this->crearTokenValido('uid-123', exp: time() - 10);

        $response = $this->withHeader('Authorization', "Bearer {$tokenExpirado}")
            ->getJson('/ed/profile');

        $response->assertStatus(401);
    }

    public function test_token_valido_en_get_profile_crea_y_resuelve_usuario_correcto(): void
    {
        $token = $this->crearTokenValido('uid-firebase-nuevo', 'nuevo@example.com', 'Juan Perez');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/ed/profile');

        $response->assertSuccessful();
        $response->assertJsonPath('data.firebase_uid', 'uid-firebase-nuevo');
        $response->assertJsonPath('data.email', 'nuevo@example.com');
        $response->assertJsonPath('data.name', 'Juan Perez');

        $this->assertDatabaseHas('users', [
            'firebase_uid' => 'uid-firebase-nuevo',
            'email'        => 'nuevo@example.com',
        ]);
    }

    public function test_post_pedido_crear_sin_token_devuelve_401(): void
    {
        $response = $this->postJson('/ed/pedido/crear', [
            'productos' => [['producto_id' => 1, 'cantidad' => 1]],
        ]);

        $response->assertStatus(401);
    }

    public function test_token_valido_sin_usuario_local_devuelve_401_en_endpoints_protegidos_salvo_get_profile(): void
    {
        $token = $this->crearTokenValido('uid-sin-user-local');

        // GET /profile SÍ debe funcionar (crea el usuario)
        $respProfile = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/ed/profile');
        $respProfile->assertSuccessful();

        // Para otro UID sin usuario local:
        $tokenDesconocido = $this->crearTokenValido('uid-desconocido-completamente');

        // POST /profile -> 401
        $this->withHeader('Authorization', "Bearer {$tokenDesconocido}")
            ->postJson('/ed/profile', ['name' => 'Otro'])
            ->assertStatus(401);

        // GET /carrito -> 401
        $this->withHeader('Authorization', "Bearer {$tokenDesconocido}")
            ->getJson('/ed/carrito')
            ->assertStatus(401);

        // GET /wishlist -> 401
        $this->withHeader('Authorization', "Bearer {$tokenDesconocido}")
            ->getJson('/ed/wishlist')
            ->assertStatus(401);

        // GET /mis-pedidos -> 401
        $this->withHeader('Authorization', "Bearer {$tokenDesconocido}")
            ->getJson('/ed/mis-pedidos')
            ->assertStatus(401);
    }

    public function test_aislamiento_de_datos_entre_dos_usuarios(): void
    {
        $userA = User::create([
            'name'         => 'Usuario A',
            'email'        => 'usera@example.com',
            'firebase_uid' => 'uid-user-a',
            'password'     => bcrypt('secret'),
        ]);

        $userB = User::create([
            'name'         => 'Usuario B',
            'email'        => 'userb@example.com',
            'firebase_uid' => 'uid-user-b',
            'password'     => bcrypt('secret'),
        ]);

        $producto = Producto::create([
            'nombre'         => 'Juego Test',
            'descripcion'    => 'Desc',
            'precioUnitario' => 1000,
            'stock'          => 10,
        ]);

        // Carrito de B
        Carrito::create(['user_id' => $userB->id, 'firebase_uid' => $userB->firebase_uid, 'producto_id' => $producto->id, 'cantidad' => 2]);
        // Wishlist de B
        Wishlist::create(['user_id' => $userB->id, 'firebase_uid' => $userB->firebase_uid, 'producto_id' => $producto->id]);
        // Pedido de B
        Pedido::create(['firebase_uid' => $userB->firebase_uid, 'estado' => 'pagado', 'total' => 1000, 'expira_at' => now()->addHours(72)]);

        $tokenA = $this->crearTokenValido($userA->firebase_uid, $userA->email, $userA->name);

        // Usuario A consulta perfil -> ve sus datos, no los de B
        $respProfile = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/ed/profile');
        $respProfile->assertStatus(200);
        $respProfile->assertJsonPath('data.firebase_uid', 'uid-user-a');

        // Usuario A consulta carrito -> vacío (0 elementos)
        $respCart = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/ed/carrito');
        $respCart->assertStatus(200);
        $respCart->assertJsonCount(0);

        // Usuario A consulta wishlist -> vacío
        $respWish = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/ed/wishlist');
        $respWish->assertStatus(200);
        $respWish->assertJsonCount(0);

        // Usuario A consulta mis-pedidos -> vacío
        $respOrders = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/ed/mis-pedidos');
        $respOrders->assertStatus(200);
        $respOrders->assertJsonCount(0);
    }

    public function test_kid_no_encontrado_en_cache_invalida_cache_y_reintenta_una_vez(): void
    {
        // Cachear inicialmente claves sin el kid nuevo
        Cache::put('firebase_public_keys', ['old-kid' => $this->certPem], 3600);

        // Al pedir con 'nuevo-kid', Http::fake devolverá el certPem con el kid 'nuevo-kid'
        $this->mockGoogleKeys(['nuevo-kid' => $this->certPem]);

        $token = $this->crearTokenValido('uid-123', kid: 'nuevo-kid');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/ed/profile');

        $response->assertSuccessful();

        // Si el kid sigue sin estar disponible tras la recarga:
        $this->mockGoogleKeys(['otro-kid' => $this->certPem]);

        $tokenInexistente = $this->crearTokenValido('uid-123', kid: 'kid-desconocido');

        $response2 = $this->withHeader('Authorization', "Bearer {$tokenInexistente}")
            ->getJson('/ed/profile');

        $response2->assertStatus(401);
    }
}
