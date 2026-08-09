<?php

namespace Tests\Feature;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReconciliarMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadopago.access_token', 'test_access_token');
    }

    /** @test */
    public function approved_payment_updates_order_to_paid_and_saves_mercado_pago_id_with_force_flag()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
        ]);
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 100.0,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id' => 171923261879,
                        'status' => 'approved',
                        'external_reference' => (string) $pedido->id,
                    ]
                ]
            ], 200),
        ]);

        $this->artisan('pedidos:reconciliar-mercadopago --force')
            ->expectsOutput("[RECONCILIADO] Pedido #{$pedido->id} actualizado a 'pagado' (Payment ID: 171923261879).")
            ->assertExitCode(0);

        $pedido->refresh();
        $this->assertEquals('pagado', $pedido->estado);
        $this->assertEquals('171923261879', $pedido->mercado_pago_id);
        $this->assertEquals(10, $producto->fresh()->stock); // El stock no cambia
    }

    /** @test */
    public function dry_run_by_default_does_not_modify_database()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
        ]);
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 100.0,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id' => 171923261879,
                        'status' => 'approved',
                        'external_reference' => (string) $pedido->id,
                    ]
                ]
            ], 200),
        ]);

        // Sin --force corre en modo dry-run por defecto
        $this->artisan('pedidos:reconciliar-mercadopago')
            ->expectsOutput("--- MODO DRY-RUN (SIMULACIÓN POR DEFECTO) ---")
            ->assertExitCode(0);

        $pedido->refresh();
        $this->assertEquals('pendiente', $pedido->estado);
        $this->assertNull($pedido->mercado_pago_id);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function rejected_payment_does_not_modify_order_and_reports_separately()
    {
        $producto = Producto::factory()->create(['stock' => 5]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
        ]);
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 50.0,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id' => 999888777,
                        'status' => 'rejected',
                        'external_reference' => (string) $pedido->id,
                    ]
                ]
            ], 200),
        ]);

        $this->artisan('pedidos:reconciliar-mercadopago --force')
            ->expectsOutput("[RECHAZADO DETECTADO] Pedido #{$pedido->id} tiene un pago en estado 'rejected' (Payment ID: 999888777). No se modifica el pedido.")
            ->assertExitCode(0);

        $pedido->refresh();
        $this->assertEquals('pendiente', $pedido->estado);
        $this->assertNull($pedido->mercado_pago_id);
        $this->assertEquals(5, $producto->fresh()->stock);
    }

    /** @test */
    public function order_without_payments_is_not_modified()
    {
        $producto = Producto::factory()->create(['stock' => 8]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
        ]);
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 80.0,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $this->artisan('pedidos:reconciliar-mercadopago --force')
            ->expectsOutput("[SIN PAGO] Pedido #{$pedido->id} no tiene pagos registrados en MercadoPago.")
            ->assertExitCode(0);

        $pedido->refresh();
        $this->assertEquals('pendiente', $pedido->estado);
        $this->assertNull($pedido->mercado_pago_id);
        $this->assertEquals(8, $producto->fresh()->stock);
    }

    /** @test */
    public function command_run_twice_is_idempotent_and_does_not_duplicate_changes_or_modify_stock()
    {
        $producto = Producto::factory()->create(['stock' => 15]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'mercado_pago_id' => null,
        ]);
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => 100.0,
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id' => 171923261879,
                        'status' => 'approved',
                        'external_reference' => (string) $pedido->id,
                    ]
                ]
            ], 200),
        ]);

        // Primera ejecución
        $this->artisan('pedidos:reconciliar-mercadopago --force')
            ->expectsOutput("[RECONCILIADO] Pedido #{$pedido->id} actualizado a 'pagado' (Payment ID: 171923261879).")
            ->assertExitCode(0);

        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals(15, $producto->fresh()->stock);

        // Segunda ejecución (el pedido ya no está en estado 'pendiente', por ende la query de la command no lo incluye)
        $this->artisan('pedidos:reconciliar-mercadopago --force')
            ->expectsOutput("Se encontraron 0 pedidos en estado 'pendiente'.")
            ->assertExitCode(0);

        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals('171923261879', $pedido->fresh()->mercado_pago_id);
        $this->assertEquals(15, $producto->fresh()->stock);
    }
}
