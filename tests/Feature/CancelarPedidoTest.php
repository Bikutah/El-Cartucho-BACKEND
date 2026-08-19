<?php

namespace Tests\Feature;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\PedidoHistorialEstado;
use App\Models\Producto;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CancelarPedidoTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-cancelar';
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
            'firebase_uid' => 'test_uid_cancelar_123',
            'email'        => 'cancelar@ejemplo.com',
        ]);

        config(['services.mercadopago.access_token' => 'TEST-ACCESS-TOKEN-12345']);

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

    // ─── GET /ed/pedido/pendiente Tests ──────────────────────────────────────────

    /** @test */
    public function obtener_pedido_pendiente_vigente_lo_devuelve()
    {
        $pedido = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(15),
            'mercado_pago_init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123',
            'total'                   => 5000.00,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200)
            ->assertJson([
                'id'                    => $pedido->id,
                'total'                 => 5000.00,
                'init_point_disponible' => true,
            ]);

        $this->assertArrayHasKey('expira_at', $response->json());
        $this->assertArrayNotHasKey('mercado_pago_init_point', $response->json());
        $this->assertArrayNotHasKey('init_point', $response->json());
    }

    /** @test */
    public function obtener_pedido_pendiente_vencido_devuelve_null()
    {
        Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->subMinutes(5),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function obtener_pedido_pendiente_usuario_sin_pedidos_pendientes_devuelve_null()
    {
        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function obtener_pedido_pendiente_usuario_con_pedido_pagado_devuelve_null()
    {
        Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function obtener_pedido_pendiente_no_devuelve_pedidos_de_otros_usuarios()
    {
        $otroUsuario = User::factory()->create([
            'firebase_uid' => 'otro_uid_456',
        ]);

        Pedido::factory()->create([
            'user_id'      => $otroUsuario->id,
            'firebase_uid' => $otroUsuario->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function obtener_pedido_pendiente_no_incluye_clave_init_point()
    {
        Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => $this->user->firebase_uid,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(15),
            'mercado_pago_init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=999',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('mercado_pago_init_point', $response->json());
        $this->assertArrayNotHasKey('init_point', $response->json());
        $this->assertTrue($response->json('init_point_disponible'));
    }

    /** @test */
    public function pedido_pendiente_con_user_id_seteado_y_firebase_uid_null_aparece_en_pedido_pendiente()
    {
        $pedido = Pedido::factory()->create([
            'user_id'                 => $this->user->id,
            'firebase_uid'            => null,
            'estado_pago'             => 'pendiente',
            'expira_at'               => now()->addMinutes(15),
            'mercado_pago_init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123',
            'total'                   => 5000.00,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $pedido->id,
            ]);
    }

    /** @test */
    public function pedido_pendiente_con_preference_id_pero_sin_init_point_devuelve_init_point_disponible_false()
    {
        $pedido = Pedido::factory()->create([
            'user_id'                  => $this->user->id,
            'firebase_uid'             => $this->user->firebase_uid,
            'estado_pago'              => 'pendiente',
            'expira_at'                => now()->addMinutes(15),
            'mercado_pago_preference_id' => 'PREF-123456',
            'mercado_pago_init_point'  => null,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200)
            ->assertJson([
                'id'                    => $pedido->id,
                'init_point_disponible' => false,
            ]);
    }

    // ─── POST /ed/pedido/{id}/cancelar Tests ────────────────────────────────────

    /** @test */
    public function cancelar_pedido_pendiente_sin_pago_vivo_en_mp_retorna_200_expirado_y_restituye_stock()
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 1000,
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Pedido cancelado correctamente']);

        $pedido->refresh();
        $this->assertEquals('expirado', $pedido->estado_pago);

        $producto->refresh();
        $this->assertEquals(7, $producto->stock);
    }

    /** @test */
    public function cancelar_pedido_con_pago_approved_en_mp_retorna_409_pago_en_curso_sin_modificar_estado_ni_stock()
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 1000,
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    ['id' => 9999, 'status' => 'approved'],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'PAGO_EN_CURSO',
            ]);

        $pedido->refresh();
        $this->assertEquals('pendiente', $pedido->estado_pago);

        $producto->refresh();
        $this->assertEquals(5, $producto->stock);
    }

    /** @test */
    public function cancelar_pedido_mp_devuelve_error_500_retorna_409_pago_en_curso_falla_cerrado()
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 1000,
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['error' => 'Internal Error'], 500),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'PAGO_EN_CURSO',
            ]);

        $pedido->refresh();
        $this->assertEquals('pendiente', $pedido->estado_pago);

        $producto->refresh();
        $this->assertEquals(5, $producto->stock);
    }

    /** @test */
    public function cancelar_pedido_pagado_retorna_409_estado_no_valido()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'ESTADO_NO_VALIDO',
            ]);

        $pedido->refresh();
        $this->assertEquals('pagado', $pedido->estado_pago);
    }

    /** @test */
    public function cancelar_pedido_de_otro_usuario_retorna_403()
    {
        $otroUsuario = User::factory()->create([
            'firebase_uid' => 'otro_uid_999',
        ]);

        $pedido = Pedido::factory()->create([
            'user_id'      => $otroUsuario->id,
            'firebase_uid' => $otroUsuario->firebase_uid,
            'estado_pago'  => 'pendiente',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(403);
    }

    /** @test */
    public function cancelar_dos_veces_no_restituye_stock_dos_veces()
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 1000,
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        // Primera cancelación -> 200
        $res1 = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");
        $res1->assertStatus(200);

        $producto->refresh();
        $this->assertEquals(7, $producto->stock);

        // Segunda cancelación -> 409 ESTADO_NO_VALIDO
        $res2 = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");
        $res2->assertStatus(409)->assertJson(['code' => 'ESTADO_NO_VALIDO']);

        $producto->refresh();
        $this->assertEquals(7, $producto->stock);
    }

    /** @test */
    public function cancelar_pedido_crea_registro_en_historial_con_observacion_cancelado_por_usuario()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson("/ed/pedido/{$pedido->id}/cancelar");

        $response->assertStatus(200);

        $this->assertDatabaseHas('pedido_historial_estados', [
            'pedido_id'       => $pedido->id,
            'tipo'            => 'pago',
            'estado_anterior' => 'pendiente',
            'estado_nuevo'    => 'expirado',
            'origen'          => 'cliente',
            'observacion'     => 'cancelado_por_usuario',
        ]);
    }
}
