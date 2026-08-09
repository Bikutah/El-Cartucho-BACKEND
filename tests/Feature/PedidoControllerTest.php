<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKeyPem;
    private string $certPem;
    private string $kid = 'test-kid-pedido';
    private string $projectId = 'el-cartucho-test-project';
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadopago.access_token', 'test_access_token');
        Config::set('mercadopago.front_url', 'http://localhost:3000');
        Config::set('mercadopago.notification_url', 'http://localhost/webhook');
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

        $this->user = User::factory()->create(['firebase_uid' => 'test-pedido-uid']);

        Http::fake([
            'https://www.googleapis.com/*' => Http::response([
                $this->kid => $this->certPem,
            ], 200, ['Cache-Control' => 'max-age=3600']),
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
    public function creating_order_with_quantity_exceeding_stock_fails_with_409()
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precioUnitario' => 100.0]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 6, // Excede stock de 5
                    ]
                ]
            ]);

        $response->assertStatus(409);
        $response->assertJsonStructure(['error', 'message']);
        $this->assertEquals(5, $producto->fresh()->stock); // El stock no se modifica
    }

    /** @test */
    public function concurrent_orders_on_last_item_one_succeeds_other_fails()
    {
        $producto = Producto::factory()->create(['stock' => 1, 'precioUnitario' => 100.0]);

        // Primer pedido por el único elemento
        $response1 = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response1->assertStatus(201);
        $this->assertEquals(0, $producto->fresh()->stock); // Queda en 0

        // Segundo pedido concurrente (ya no hay stock)
        $response2 = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ]
            ]);

        $response2->assertStatus(409); // Falla con 409
    }

    /** @test */
    public function order_creation_with_web_session_but_no_firebase_token_returns_401()
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precioUnitario' => 100.0]);

        // Iniciar sesión en Laravel (sesión web / actingAs) pero SIN enviar header Authorization Bearer
        $response = $this->actingAs($this->user)
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad'    => 1,
                    ]
                ],
                'email' => 'test@example.com',
                'codigo_postal' => '1234',
            ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token de autorización ausente.']);
    }

    /** @test */
    public function order_created_via_post_saves_user_id_and_address_snapshot_and_updates_client_stats()
    {
        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 500.0]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 2]
                ],
                'email'         => 'compras@ejemplo.com',
                'domicilio'     => 'Av. Corrientes 1234',
                'ciudad'        => 'Buenos Aires',
                'codigo_postal' => '1043',
            ]);

        $response->assertStatus(201);

        $pedidoId = $response->json('pedido_id');
        $this->assertNotNull($pedidoId);

        $pedido = \App\Models\Pedido::find($pedidoId);
        $this->assertNotNull($pedido);
        $this->assertEquals($this->user->id, $pedido->user_id);
        $this->assertEquals('compras@ejemplo.com', $pedido->email);
        $this->assertEquals('Av. Corrientes 1234', $pedido->domicilio);
        $this->assertEquals('Buenos Aires', $pedido->ciudad);
        $this->assertEquals('1043', $pedido->codigo_postal);

        // Verificar que aparece con el nombre en el panel admin
        $admin = User::factory()->create(['name' => 'Admin']);
        $this->actingAs($admin)
            ->get('/pedidos')
            ->assertStatus(200)
            ->assertSee($this->user->name);

        // Verificar que suma a las estadísticas del cliente en el panel
        $this->actingAs($admin)
            ->get("/clientes/{$this->user->id}")
            ->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee('1.000,00'); // 2 * 500
    }

    /** @test */
    public function order_retains_original_address_snapshot_even_if_user_changes_profile_later()
    {
        $producto = Producto::factory()->create(['stock' => 10, 'precioUnitario' => 200.0]);

        $response = $this->withHeaders($this->tokenHeader())
            ->postJson('/ed/pedido/crear', [
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 1]
                ],
                'email'         => 'original@ejemplo.com',
                'domicilio'     => 'Calle Vieja 100',
                'ciudad'        => 'Córdoba',
                'codigo_postal' => '5000',
            ]);

        $response->assertStatus(201);
        $pedidoId = $response->json('pedido_id');

        // El usuario actualiza su perfil a otra provincia / ciudad
        $this->user->update([
            'domicilio'     => 'Calle Nueva 999',
            'ciudad'        => 'Mendoza',
            'codigo_postal' => '5500',
        ]);

        $pedido = \App\Models\Pedido::find($pedidoId);
        $this->assertEquals('Calle Vieja 100', $pedido->domicilio);
        $this->assertEquals('Córdoba', $pedido->ciudad);
        $this->assertEquals('5000', $pedido->codigo_postal);
    }

    /** @test */
    public function product_stock_availability_filter_test()
    {
        $prodConStock = Producto::factory()->create(['stock' => 10, 'nombre' => 'Juego Con Stock']);
        $prodSinStock = Producto::factory()->create(['stock' => 0, 'nombre' => 'Juego Agotado']);

        // Sin filtro -> trae ambos
        $respTodo = $this->getJson('/ed/producto/listar');
        $respTodo->assertStatus(200);
        $respTodo->assertSee('Juego Con Stock');
        $respTodo->assertSee('Juego Agotado');

        // Con filtro disponibilidad=con_stock -> solo trae con stock
        $respConStock = $this->getJson('/ed/producto/listar?disponibilidad=con_stock');
        $respConStock->assertStatus(200);
        $respConStock->assertSee('Juego Con Stock');
        $respConStock->assertDontSee('Juego Agotado');

        // Con filtro disponibilidad=sin_stock -> solo trae agotado
        $respSinStock = $this->getJson('/ed/producto/listar?disponibilidad=sin_stock');
        $respSinStock->assertStatus(200);
        $respSinStock->assertSee('Juego Agotado');
        $respSinStock->assertDontSee('Juego Con Stock');

        // Filtro inválido -> 422
        $respInvalido = $this->getJson('/ed/producto/listar?disponibilidad=invalido');
        $respInvalido->assertStatus(422);
    }

    /** @test */
    public function order_detail_view_shows_payment_id_and_preference_id_when_present()
    {
        $admin = User::factory()->create(['name' => 'Admin Test']);
        $pedido = \App\Models\Pedido::factory()->create([
            'user_id' => $this->user->id,
            'estado' => 'pagado',
            'mercado_pago_id' => '172836453442',
            'mercado_pago_preference_id' => '2459460394-6b357e0c-fd7b-45fc-87b1-1234567890ab',
        ]);

        $response = $this->actingAs($admin)->get("/pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $response->assertSee('172836453442');
        $response->assertSee('2459460394-6b357e0c-fd7b-45fc-87b1-1234567890ab');
        $response->assertSee('(Referencia externa MP)');
        $response->assertSee('aria-label="Copiar Payment ID"', false);
        $response->assertSee('aria-label="Copiar Preference ID"', false);
    }

    /** @test */
    public function order_detail_view_without_payment_id_shows_neutral_text_and_does_not_break()
    {
        $admin = User::factory()->create(['name' => 'Admin Test 2']);
        $pedido = \App\Models\Pedido::factory()->create([
            'user_id' => $this->user->id,
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
            'mercado_pago_preference_id' => null,
        ]);

        $response = $this->actingAs($admin)->get("/pedidos/{$pedido->id}");

        $response->assertStatus(200);
        $response->assertSee('Sin pago registrado');
        $response->assertSee('(Referencia externa MP)');
    }
}
