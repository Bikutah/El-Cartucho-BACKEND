<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\PedidoHistorialEstado;
use App\Models\Producto;
use App\Models\User;
use App\Models\ZonaEnvio;
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

    /** @test */
    public function store_con_pedido_pendiente_vigente_retorna_409_pedido_pendiente_existente_no_crea_pedido_ni_modifica_stock()
    {
        $zona = ZonaEnvio::create(['nombre' => 'Zona 1000', 'cp_desde' => 1000, 'cp_hasta' => 1000, 'costo' => 500, 'activa' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 10]);

        $pedidoExistente = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->addMinutes(15),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '1000',
                'productos'     => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'code'      => 'PEDIDO_PENDIENTE_EXISTENTE',
                'pedido_id' => $pedidoExistente->id,
            ]);

        $this->assertEquals(10, $producto->fresh()->stock);
        $this->assertDatabaseCount('pedidos', 1);
    }

    /** @test */
    public function store_con_pedido_pendiente_vencido_permite_crear_nuevo_pedido()
    {
        $zona = ZonaEnvio::create(['nombre' => 'Zona 1000', 'cp_desde' => 1000, 'cp_hasta' => 1000, 'costo' => 500, 'activa' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 10]);

        Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pendiente',
            'expira_at'    => now()->subMinutes(5),
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF-NEW', 'init_point' => 'https://mp.com/init'], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '1000',
                'productos'     => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(8, $producto->fresh()->stock);
    }

    /** @test */
    public function store_con_pedido_pagado_permite_crear_nuevo_pedido()
    {
        $zona = ZonaEnvio::create(['nombre' => 'Zona 1000', 'cp_desde' => 1000, 'cp_hasta' => 1000, 'costo' => 500, 'activa' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 10]);

        Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF-NEW', 'init_point' => 'https://mp.com/init'], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '1000',
                'productos'     => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(8, $producto->fresh()->stock);
    }

    /** @test */
    public function store_vacia_del_carrito_los_items_del_pedido_y_mantiene_los_no_incluidos()
    {
        $zona = ZonaEnvio::create(['nombre' => 'Zona 1000', 'cp_desde' => 1000, 'cp_hasta' => 1000, 'costo' => 500, 'activa' => true, 'orden' => 1]);
        $prod1 = Producto::factory()->create(['stock' => 10]);
        $prod2 = Producto::factory()->create(['stock' => 10]);

        Carrito::create(['user_id' => $this->user->id, 'firebase_uid' => $this->user->firebase_uid, 'producto_id' => $prod1->id, 'cantidad' => 2]);
        Carrito::create(['user_id' => $this->user->id, 'firebase_uid' => $this->user->firebase_uid, 'producto_id' => $prod2->id, 'cantidad' => 3]);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([$this->kid => $this->certPem], 200),
            'https://api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF-1', 'init_point' => 'https://mp.com/1'], 200),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '1000',
                'productos'     => [
                    ['producto_id' => $prod1->id, 'cantidad' => 2],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('carrito', ['user_id' => $this->user->id, 'producto_id' => $prod1->id]);
        $this->assertDatabaseHas('carrito', ['user_id' => $this->user->id, 'producto_id' => $prod2->id, 'cantidad' => 3]);
    }

    /** @test */
    public function obtener_pedido_pendiente_devuelve_array_de_productos()
    {
        $producto = Producto::factory()->create(['nombre' => 'Juego Test', 'precioUnitario' => 1500]);

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
            'precio_unitario' => 1500,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/pedido/pendiente');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $pedido->id,
                'productos' => [
                    [
                        'producto_id'     => $producto->id,
                        'nombre'          => 'Juego Test',
                        'cantidad'        => 2,
                        'precio_unitario' => 1500,
                    ]
                ]
            ]);
    }

    /** @test */
    public function cancelar_pedido_repone_items_al_carrito_con_cantidad_correcta()
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
            ->assertJson([
                'message'               => 'Pedido cancelado correctamente',
                'reposicion_carrito_ok' => true,
                'ajustes'               => [],
            ]);

        $this->assertDatabaseHas('carrito', [
            'user_id'     => $this->user->id,
            'producto_id' => $producto->id,
            'cantidad'    => 2,
        ]);
    }

    /** @test */
    public function cancelar_pedido_suma_cantidades_si_el_producto_ya_estaba_en_el_carrito()
    {
        $producto = Producto::factory()->create(['stock' => 5]);

        Carrito::create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'producto_id'  => $producto->id,
            'cantidad'     => 1,
        ]);

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

        $response->assertStatus(200);

        $this->assertDatabaseHas('carrito', [
            'user_id'     => $this->user->id,
            'producto_id' => $producto->id,
            'cantidad'    => 3,
        ]);
    }

    /** @test */
    public function cancelar_pedido_topea_cantidad_al_stock_disponible_y_reporta_ajuste()
    {
        $producto = Producto::factory()->create(['nombre' => 'Super Mario', 'stock' => 2]);

        Carrito::create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'producto_id'  => $producto->id,
            'cantidad'     => 3,
        ]);

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
            ->assertJson([
                'message'               => 'Pedido cancelado correctamente',
                'reposicion_carrito_ok' => true,
                'ajustes'               => [
                    [
                        'producto_id'         => $producto->id,
                        'nombre'              => 'Super Mario',
                        'cantidad_solicitada' => 5,
                        'cantidad_final'      => 4,
                    ]
                ],
            ]);

        $this->assertDatabaseHas('carrito', [
            'user_id'     => $this->user->id,
            'producto_id' => $producto->id,
            'cantidad'    => 4,
        ]);
    }

    /** @test */
    public function carrito_controller_upsert_devuelve_cantidad_guardada_y_hubo_recorte()
    {
        $producto = Producto::factory()->create(['stock' => 3]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/carrito', [
                'producto_id' => $producto->id,
                'cantidad'    => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'producto_id'       => $producto->id,
                'cantidad'          => 3,
                'cantidad_guardada' => 3,
                'hubo_recorte'      => true,
            ]);
    }
}
