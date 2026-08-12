<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\ZonaEnvio;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZonaEnvioTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-zona';
    private string $projectId = 'el-cartucho-test-project';
    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadopago.access_token', 'test_access_token');
        Config::set('services.mercadopago.front_url', 'http://localhost:3000');
        Config::set('mercadopago.expiration_hours', 72);

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
            'firebase_uid'  => 'test-user-uid',
            'codigo_postal' => '9100',
        ]);

        $this->adminUser = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                $this->kid => $this->certPem,
            ], 200, ['Cache-Control' => 'max-age=3600']),
            'http://api.zippopotam.us/ar/*' => Http::response([
                'country' => 'Argentina',
                'places'  => [['place name' => 'Trelew', 'state' => 'Chubut']]
            ], 200),
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'init_point' => 'https://mercadopago.com/checkout/pay',
                'id'         => 'pref_12345',
            ], 200),
        ]);
    }

    private function tokenHeader(): array
    {
        $payload = [
            'iss'   => "https://securetoken.google.com/{$this->projectId}",
            'aud'   => $this->projectId,
            'sub'   => $this->user->firebase_uid,
            'email' => $this->user->email,
            'name'  => $this->user->name,
            'iat'   => time() - 10,
            'exp'   => time() + 3600,
        ];
        $token = JWT::encode($payload, $this->privateKeyPem, 'RS256', $this->kid);
        return ['Authorization' => "Bearer {$token}"];
    }

    /** @test */
    public function test_1_para_codigo_postal_resolves_correct_zone_by_range()
    {
        $zonaChubut = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $resolved = ZonaEnvio::paraCodigoPostal('9100');

        $this->assertNotNull($resolved);
        $this->assertEquals($zonaChubut->id, $resolved->id);
        $this->assertEquals('Chubut', $resolved->nombre);
    }

    /** @test */
    public function test_2_overlapping_ranges_resolve_to_zone_with_lowest_orden()
    {
        $zonaGeneral = ZonaEnvio::create([
            'nombre'   => 'Patagonia',
            'cp_desde' => 8300,
            'cp_hasta' => 9999,
            'costo'    => 12000,
            'activa'   => true,
            'orden'    => 1,
        ]);

        $zonaEspecial = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $resolved = ZonaEnvio::paraCodigoPostal('9100');

        $this->assertNotNull($resolved);
        $this->assertEquals($zonaEspecial->id, $resolved->id);
        $this->assertEquals('Chubut', $resolved->nombre);
    }

    /** @test */
    public function test_3_alphanumeric_cp_is_normalized_and_resolved()
    {
        $zonaChubut = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $resolved = ZonaEnvio::paraCodigoPostal('U9100AAA');

        $this->assertNotNull($resolved);
        $this->assertEquals($zonaChubut->id, $resolved->id);
    }

    /** @test */
    public function test_4_cp_without_zone_returns_422_instead_of_default_cost()
    {
        // No hay zonas creadas
        $response = $this->getJson('/ed/pedido/costo/9100');

        $response->assertStatus(422);
        $response->assertJson([
            'error'   => 'No realizamos envíos a ese código postal',
            'message' => 'No realizamos envíos a ese código postal',
        ]);
    }

    /** @test */
    public function test_5_inactive_zone_is_not_considered_in_resolution()
    {
        ZonaEnvio::create([
            'nombre'   => 'Chubut Inactiva',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => false,
            'orden'    => 0,
        ]);

        $resolved = ZonaEnvio::paraCodigoPostal('9100');
        $this->assertNull($resolved);

        $response = $this->getJson('/ed/pedido/costo/9100');
        $response->assertStatus(422);
    }

    /** @test */
    public function test_6_creating_order_calculates_total_as_products_plus_shipping_cost()
    {
        $zona = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 1000.00]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '9100',
                'productos'     => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 2, // Subtotal productos = 2000
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $pedidoId = $response->json('pedido_id');

        $pedido = Pedido::find($pedidoId);
        $this->assertNotNull($pedido);
        $this->assertEquals(10000.00, (float)$pedido->total); // 2000 productos + 8000 envío
    }

    /** @test */
    public function test_7_creating_order_persists_costo_envio_and_zona_envio_id()
    {
        $zona = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 500.00]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '9100',
                'productos'     => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $pedido = Pedido::find($response->json('pedido_id'));

        $this->assertEquals(8000.00, (float)$pedido->costo_envio);
        $this->assertEquals($zona->id, $pedido->zona_envio_id);
    }

    /** @test */
    public function test_8_client_provided_shipping_cost_in_request_is_ignored()
    {
        $zona = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 1000.00]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '9100',
                'costo_envio'   => 1, // Intentar falsear costo a $1
                'productos'     => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $pedido = Pedido::find($response->json('pedido_id'));

        $this->assertEquals(8000.00, (float)$pedido->costo_envio);
        $this->assertEquals(9000.00, (float)$pedido->total); // 1000 + 8000
    }

    /** @test */
    public function test_9_creating_order_with_cp_without_zone_returns_422_and_does_not_create_order()
    {
        // No se crean zonas
        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 1000.00]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'codigo_postal' => '9100',
                'productos'     => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error'   => 'No realizamos envíos a ese código postal',
            'message' => 'No realizamos envíos a ese código postal',
        ]);
        $this->assertEquals(0, Pedido::count());
    }

    /** @test */
    public function test_10_admin_panel_crud_for_zonas_envio()
    {
        // Crear
        $createResponse = $this->actingAs($this->adminUser)
            ->post('/zonas-envio', [
                'nombre'   => 'Norte',
                'cp_desde' => 3000,
                'cp_hasta' => 4999,
                'costo'    => 18000,
                'orden'    => 0,
                'activa'   => '1',
            ]);

        $createResponse->assertRedirect(route('zonas-envio.index'));
        $this->assertDatabaseHas('zonas_envio', [
            'nombre'   => 'Norte',
            'cp_desde' => 3000,
            'cp_hasta' => 4999,
            'costo'    => 18000,
        ]);

        $zona = ZonaEnvio::where('nombre', 'Norte')->first();

        // Editar
        $updateResponse = $this->actingAs($this->adminUser)
            ->put("/zonas-envio/{$zona->id}", [
                'nombre'   => 'Norte Modificado',
                'cp_desde' => 3000,
                'cp_hasta' => 4999,
                'costo'    => 20000,
                'orden'    => 1,
                'activa'   => '1',
            ]);

        $updateResponse->assertRedirect(route('zonas-envio.index'));
        $this->assertDatabaseHas('zonas_envio', [
            'id'     => $zona->id,
            'nombre' => 'Norte Modificado',
            'costo'  => 20000,
        ]);

        // Eliminar
        $deleteResponse = $this->actingAs($this->adminUser)
            ->delete("/zonas-envio/{$zona->id}");

        $deleteResponse->assertRedirect(route('zonas-envio.index'));
        $this->assertDatabaseMissing('zonas_envio', ['id' => $zona->id]);
    }

    /** @test */
    public function test_11_deleting_zone_does_not_break_referencing_orders_and_sets_foreign_key_to_null()
    {
        $zona = ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $pedido = Pedido::factory()->create([
            'costo_envio'   => 8000,
            'zona_envio_id' => $zona->id,
        ]);

        $this->actingAs($this->adminUser)
            ->delete("/zonas-envio/{$zona->id}");

        $this->assertDatabaseMissing('zonas_envio', ['id' => $zona->id]);
        $this->assertNull($pedido->fresh()->zona_envio_id);
        $this->assertEquals(8000, (float)$pedido->fresh()->costo_envio); // Conserva el costo como snapshot
    }

    /** @test */
    public function test_12_cp_with_fewer_than_4_digits_returns_null_and_endpoint_returns_422()
    {
        ZonaEnvio::create([
            'nombre'   => 'Centro',
            'cp_desde' => 10,
            'cp_hasta' => 99,
            'costo'    => 5000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $this->assertNull(ZonaEnvio::paraCodigoPostal('12'));

        $response = $this->getJson('/ed/pedido/costo/12');
        $response->assertStatus(422);
    }

    /** @test */
    public function test_13_cp_with_more_than_4_digits_does_not_silently_resolve_and_returns_422()
    {
        ZonaEnvio::create([
            'nombre'   => 'Chubut',
            'cp_desde' => 9000,
            'cp_hasta' => 9299,
            'costo'    => 8000,
            'activa'   => true,
            'orden'    => 0,
        ]);

        $this->assertNull(ZonaEnvio::paraCodigoPostal('91000'));
        $this->assertNull(ZonaEnvio::paraCodigoPostal('123456'));

        $response = $this->getJson('/ed/pedido/costo/91000');
        $response->assertStatus(422);
    }
}
