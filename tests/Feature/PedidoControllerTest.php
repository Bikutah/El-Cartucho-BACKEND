<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadopago.access_token', 'test_access_token');
        Config::set('mercadopago.front_url', 'http://localhost:3000');
        Config::set('mercadopago.notification_url', 'http://localhost/webhook');
        Config::set('mercadopago.expiration_hours', 72);

        $user = \App\Models\User::factory()->create(['firebase_uid' => 'test-pedido-uid']);
        $this->actingAs($user);
        $this->withoutMiddleware([
            \App\Http\Middleware\VerificarTokenFirebase::class,
            \App\Http\Middleware\RequerirUsuarioLocal::class,
        ]);
    }

    /** @test */
    public function creating_order_with_quantity_exceeding_stock_fails_with_409()
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precioUnitario' => 100.0]);

        $response = $this->postJson('/ed/pedido/crear', [
            'productos' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 6 // Excede stock de 5
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

        // Mock de MercadoPago para la creación de preferencia
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'init_point' => 'https://mercadopago.com/checkout/pay',
                'id' => 'pref_12345'
            ], 200)
        ]);

        // Primer pedido por el único elemento
        $response1 = $this->postJson('/ed/pedido/crear', [
            'productos' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 1
                ]
            ]
        ]);

        $response1->assertStatus(201);
        $this->assertEquals(0, $producto->fresh()->stock); // Queda en 0

        // Segundo pedido concurrente (ya no hay stock)
        $response2 = $this->postJson('/ed/pedido/crear', [
            'productos' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 1
                ]
            ]
        ]);

        $response2->assertStatus(409); // Falla con 409
    }
}
