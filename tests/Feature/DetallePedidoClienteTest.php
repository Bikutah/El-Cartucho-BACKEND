<?php

namespace Tests\Feature;

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

class DetallePedidoClienteTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-detalle';
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
            'firebase_uid' => 'test_uid_detalle_cliente_123',
            'email'        => 'cliente_detalle@ejemplo.com',
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

    /** @test */
    public function test_1_usuario_ve_el_detalle_de_su_propio_pedido()
    {
        $zona = ZonaEnvio::create(['nombre' => 'Chubut', 'cp_desde' => 9000, 'cp_hasta' => 9200, 'costo' => 12000, 'orden' => 1, 'activo' => true]);
        $producto = Producto::factory()->create(['precioUnitario' => 16500]);

        $pedido = Pedido::factory()->create([
            'user_id'         => $this->user->id,
            'firebase_uid'    => $this->user->firebase_uid,
            'estado_pago'     => 'pagado',
            'estado_envio'    => 'enviado',
            'costo_envio'     => 12000,
            'total'           => 45000,
            'zona_envio_id'   => $zona->id,
            'domicilio'       => 'Av San Martin 123',
            'ciudad'          => 'Trelew',
            'codigo_postal'   => '9100',
            'email'           => $this->user->email,
            'transportista'   => 'Correo Argentino',
            'tracking_numero' => 'CA123456789',
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 16500,
        ]);

        $adminUser = User::factory()->create();
        PedidoHistorialEstado::create([
            'pedido_id'       => $pedido->id,
            'tipo'            => 'envio',
            'estado_anterior' => 'preparando',
            'estado_nuevo'    => 'enviado',
            'user_id'         => $adminUser->id,
            'origen'          => 'panel',
            'observacion'     => 'Despachado',
            'created_at'      => now(),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'estado_pago',
            'estado_envio',
            'estado_visible',
            'created_at',
            'total',
            'subtotal_productos',
            'costo_envio',
            'zona_envio',
            'envio' => [
                'domicilio',
                'ciudad',
                'codigo_postal',
                'email',
                'transportista',
                'tracking_numero',
                'enviado_at',
                'entregado_at',
            ],
            'productos' => [
                '*' => ['nombre', 'cantidad', 'precio_unitario', 'subtotal', 'imagen']
            ],
            'historial' => [
                '*' => ['estado', 'fecha']
            ]
        ]);

        $this->assertEquals(45000, $response->json('total'));
        $this->assertEquals(33000, $response->json('subtotal_productos'));
        $this->assertEquals(12000, $response->json('costo_envio'));
        $this->assertEquals('Chubut', $response->json('zona_envio'));
        $this->assertEquals('CA123456789', $response->json('envio.tracking_numero'));
    }

    /** @test */
    public function test_2_usuario_pide_el_detalle_de_un_pedido_ajeno_devuelve_404()
    {
        $otroUser = User::factory()->create(['firebase_uid' => 'otro_uid_999']);
        $pedidoAjeno = Pedido::factory()->create([
            'user_id'      => $otroUser->id,
            'firebase_uid' => $otroUser->firebase_uid,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedidoAjeno->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function test_3_sin_token_de_firebase_devuelve_401()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
        ]);

        $response = $this->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function test_4_pedido_inexistente_devuelve_404()
    {
        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/999999");

        $response->assertStatus(404);
    }

    /** @test */
    public function test_5_el_historial_no_incluye_user_id_origen_ni_observacion()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        $admin = User::factory()->create();
        PedidoHistorialEstado::create([
            'pedido_id'       => $pedido->id,
            'tipo'            => 'envio',
            'estado_anterior' => 'sin_preparar',
            'estado_nuevo'    => 'preparando',
            'user_id'         => $admin->id,
            'origen'          => 'panel',
            'observacion'     => 'Se comenzo empaque',
            'created_at'      => now(),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(200);

        $historial = $response->json('historial');
        $this->assertNotEmpty($historial);

        foreach ($historial as $item) {
            $this->assertArrayNotHasKey('user_id', $item);
            $this->assertArrayNotHasKey('origen', $item);
            $this->assertArrayNotHasKey('observacion', $item);
        }
    }

    /** @test */
    public function test_6_el_historial_excluye_las_entradas_con_origen_sistema()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado_pago'  => 'pagado',
        ]);

        PedidoHistorialEstado::create([
            'pedido_id'       => $pedido->id,
            'tipo'            => 'pago',
            'estado_anterior' => null,
            'estado_nuevo'    => 'pendiente',
            'user_id'         => null,
            'origen'          => 'sistema',
            'observacion'     => 'Creacion inicial',
            'created_at'      => now()->subMinutes(10),
        ]);

        $admin = User::factory()->create();
        PedidoHistorialEstado::create([
            'pedido_id'       => $pedido->id,
            'tipo'            => 'envio',
            'estado_anterior' => 'sin_preparar',
            'estado_nuevo'    => 'preparando',
            'user_id'         => $admin->id,
            'origen'          => 'panel',
            'observacion'     => 'Empacado',
            'created_at'      => now(),
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $historial = $response->json('historial');

        $this->assertCount(1, $historial);
        $this->assertEquals('Preparando tu pedido', $historial[0]['estado']);
    }

    /** @test */
    public function test_7_subtotal_productos_mas_costo_envio_es_igual_a_total()
    {
        $pedido = Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'costo_envio'  => 5000,
            'total'        => 25000,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(200);

        $subtotal = $response->json('subtotal_productos');
        $costo = $response->json('costo_envio');
        $total = $response->json('total');

        $this->assertEquals(20000, $subtotal);
        $this->assertEquals(5000, $costo);
        $this->assertEquals(25000, $total);
        $this->assertEquals($total, $subtotal + $costo);
    }

    /** @test */
    public function test_8_un_pedido_sin_tracking_devuelve_los_campos_en_null_sin_romper()
    {
        $pedido = Pedido::factory()->create([
            'user_id'         => $this->user->id,
            'firebase_uid'    => $this->user->firebase_uid,
            'transportista'   => null,
            'tracking_numero' => null,
            'enviado_at'      => null,
            'entregado_at'    => null,
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson("/ed/mis-pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $this->assertNull($response->json('envio.transportista'));
        $this->assertNull($response->json('envio.tracking_numero'));
        $this->assertNull($response->json('envio.enviado_at'));
        $this->assertNull($response->json('envio.entregado_at'));
    }

    /** @test */
    public function test_9_mis_pedidos_sigue_devolviendo_la_clave_estado_para_regresion()
    {
        Pedido::factory()->create([
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'estado'       => 'pagado',
            'estado_pago'  => 'pagado',
        ]);

        $response = $this->withHeaders($this->tokenHeader())
            ->getJson('/ed/mis-pedidos');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'estado',
                'estado_pago',
                'estado_envio',
                'estado_visible',
                'costo_envio',
                'tiene_tracking',
                'total',
                'created_at',
                'productos',
            ]
        ]);
        $this->assertEquals('pagado', $response->json('0.estado'));
        $this->assertEquals('Pago confirmado', $response->json('0.estado_visible'));
    }
}
