<?php

namespace Tests\Feature;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReintentoPagoTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-reintento';
    private string $projectId = 'el-cartucho-test-project';
    protected User $user;

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

        $dn = ["countryName" => "AR", "organizationName" => "Test", "commonName" => "Test"];
        $res = openssl_pkey_new($openSslConfig);
        openssl_pkey_export($res, $keyOut, null, $openSslConfig);
        $this->privateKeyPem = $keyOut;

        $csr = openssl_csr_new($dn, $res, $openSslConfig);
        $cert = openssl_csr_sign($csr, null, $res, 365, $openSslConfig);
        openssl_x509_export($cert, $certOut);
        $this->certPem = $certOut;

        $this->user = User::factory()->create([
            'firebase_uid' => 'test_uid_reintento_123',
            'email'        => 'reintento@ejemplo.com',
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                $this->kid => $this->certPem,
            ], 200, ['Cache-Control' => 'max-age=3600']),
        ]);
    }

    protected function tokenHeader(?User $user = null): array
    {
        $targetUser = $user ?? $this->user;
        $payload = [
            'iss'   => "https://securetoken.google.com/{$this->projectId}",
            'aud'   => $this->projectId,
            'sub'   => $targetUser->firebase_uid,
            'email' => $targetUser->email,
            'name'  => $targetUser->name,
            'iat'   => time() - 10,
            'exp'   => time() + 3600,
        ];
        $token = JWT::encode($payload, $this->privateKeyPem, 'RS256', $this->kid);
        return ['Authorization' => "Bearer {$token}"];
    }

    // ─── Estado Efectivo Accessor Tests ─────────────────────────────────────

    /** @test */
    public function estado_efectivo_pendiente_con_expira_at_futuro_devuelve_pendiente()
    {
        $pedido = Pedido::factory()->create([
            'estado_pago' => 'pendiente',
            'expira_at'   => now()->addMinutes(15),
        ]);

        $this->assertEquals('pendiente', $pedido->estado_efectivo);
    }

    /** @test */
    public function estado_efectivo_pendiente_con_expira_at_vencido_devuelve_expirado()
    {
        $pedido = Pedido::factory()->create([
            'estado_pago' => 'pendiente',
            'expira_at'   => now()->subMinutes(5),
        ]);

        $this->assertEquals('expirado', $pedido->estado_efectivo);
    }

    /** @test */
    public function estado_efectivo_pagado_devuelve_pagado_sin_importar_expira_at()
    {
        $pedido = Pedido::factory()->create([
            'estado_pago' => 'pagado',
            'expira_at'   => now()->subMinutes(10),
        ]);

        $this->assertEquals('pagado', $pedido->estado_efectivo);
    }

    /** @test */
    public function estado_efectivo_pendiente_con_expira_at_null_devuelve_pendiente()
    {
        $pedido = Pedido::factory()->create([
            'estado_pago' => 'pendiente',
            'expira_at'   => null,
        ]);

        $this->assertEquals('pendiente', $pedido->estado_efectivo);
    }

    // ─── Listado Endpoint Tests ─────────────────────────────────────────────

    /** @test */
    public function listado_mis_pedidos_incluye_pendiente_no_vencido_pagado_y_reembolsado_y_excluye_expirado()
    {
        $pedidoPendienteVigente = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(10),
            'mercado_pago_init_point' => 'https://mercadopago.com/checkout/123',
        ]);

        $pedidoPendienteVencido = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->subMinutes(10),
            'mercado_pago_init_point' => 'https://mercadopago.com/checkout/456',
        ]);

        $pedidoPagado = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        $pedidoReembolsado = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'reembolsado',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/mis-pedidos');

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id')->toArray();

        $this->assertContains($pedidoPendienteVigente->id, $ids);
        $this->assertContains($pedidoPagado->id, $ids);
        $this->assertContains($pedidoReembolsado->id, $ids);
        $this->assertNotContains($pedidoPendienteVencido->id, $ids);

        // Formato ISO 8601 con offset para expira_at
        $itemVigente = collect($response->json())->firstWhere('id', $pedidoPendienteVigente->id);
        $this->assertNotNull($itemVigente['expira_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $itemVigente['expira_at']);
        $this->assertTrue($itemVigente['init_point_disponible']);
    }

    // ─── Detalle Endpoint Tests ─────────────────────────────────────────────

    /** @test */
    public function detalle_mis_pedidos_sigue_devolviendo_un_pedido_pendiente_vencido()
    {
        $pedidoVencido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->subMinutes(15),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedidoVencido->id}");

        $response->assertStatus(200);
        $this->assertEquals($pedidoVencido->id, $response->json('id'));
        $this->assertEquals('pendiente', $response->json('estado_pago'));
        $this->assertEquals('expirado', $response->json('estado_efectivo'));
    }

    // ─── Reintento Endpoint Tests ────────────────────────────────────────────

    /** @test */
    public function reintento_exitoso_devuelve_200_con_init_point_y_expira_at()
    {
        Http::fake(); // Fake all HTTP calls to verify MercadoPago API is NOT called

        $producto = Producto::factory()->create(['stock' => 5]);
        $expiracion = now()->addMinutes(15);
        $initPoint = 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123';

        $pedido = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => $expiracion,
            'mercado_pago_init_point' => $initPoint,
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 1000,
        ]);

        $expiraAtBefore = $pedido->fresh()->expira_at->toIso8601String();
        $stockBefore = $producto->fresh()->stock;

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/reintentar-pago");

        $response->assertStatus(200);
        $response->assertJson([
            'init_point' => $initPoint,
            'expira_at'  => $expiraAtBefore,
        ]);

        // Asserts duras: no modifica expira_at, no llama a MP API, no toca stock
        $this->assertEquals($expiraAtBefore, $pedido->fresh()->expira_at->toIso8601String());
        $this->assertEquals($stockBefore, $producto->fresh()->stock);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'mercadopago.com'));
    }

    /** @test */
    public function reintento_en_pedido_de_otro_usuario_devuelve_403()
    {
        $otroUser = User::factory()->create(['firebase_uid' => 'otro_uid_888']);
        $pedidoAjeno = Pedido::factory()->create([
            'user_id'                 => $otroUser->id,
            'firebase_uid'            => $otroUser->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(10),
            'mercado_pago_init_point' => 'https://mercadopago.com/checkout/999',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedidoAjeno->id}/reintentar-pago");

        $response->assertStatus(403);
    }

    /** @test */
    public function reintento_en_pedido_pagado_devuelve_409_estado_no_valido()
    {
        $pedidoPagado = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pagado',
            'expira_at'               => now()->addMinutes(10),
            'mercado_pago_init_point' => 'https://mercadopago.com/checkout/123',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedidoPagado->id}/reintentar-pago");

        $response->assertStatus(409);
        $response->assertJson([
            'code' => 'ESTADO_NO_VALIDO',
        ]);
    }

    /** @test */
    public function reintento_en_pedido_expirado_devuelve_409_reserva_expirada()
    {
        $pedidoExpirado = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->subMinutes(5),
            'mercado_pago_init_point' => 'https://mercadopago.com/checkout/123',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedidoExpirado->id}/reintentar-pago");

        $response->assertStatus(409);
        $response->assertJson([
            'code' => 'RESERVA_EXPIRADA',
        ]);
    }

    /** @test */
    public function reintento_en_pedido_sin_init_point_devuelve_409_sin_link_pago()
    {
        $pedidoSinLink = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(10),
            'mercado_pago_init_point' => null,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedidoSinLink->id}/reintentar-pago");

        $response->assertStatus(409);
        $response->assertJson([
            'code' => 'SIN_LINK_PAGO',
        ]);
    }

    // ─── Estado Endpoint Tests ───────────────────────────────────────────────

    /** @test */
    public function endpoint_estado_devuelve_estado_efectivo_correcto_para_pendiente_vencido()
    {
        $pedidoVencido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->subMinutes(10),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/pedido/{$pedidoVencido->id}/estado");

        $response->assertStatus(200);
        $response->assertJson([
            'estado_pago'     => 'pendiente',
            'estado_efectivo' => 'expirado',
        ]);
        $this->assertNotNull($response->json('expira_at'));
    }

    /** @test */
    public function endpoint_estado_para_pedido_ajeno_devuelve_403()
    {
        $otroUser = User::factory()->create(['firebase_uid' => 'otro_uid_777']);
        $pedidoAjeno = Pedido::factory()->create([
            'user_id'      => $otroUser->id,
            'firebase_uid' => $otroUser->firebase_uid,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/pedido/{$pedidoAjeno->id}/estado");

        $response->assertStatus(403);
    }
}
