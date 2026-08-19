<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class LiberarPedidosVencidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.mercadopago.access_token', 'test_access_token');
        Config::set('mercadopago.legacy_expiration_hours', 96);
    }

    /** @test */
    public function command_cancels_expired_pending_order_without_mercado_pago_id()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => now()->subMinutes(1)
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        // Ejecutar comando
        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        $this->assertEquals(12, $producto->fresh()->stock); // 10 + 2
    }

    /** @test */
    public function command_does_not_cancel_expired_pending_order_with_valid_pending_payment_in_mercado_pago()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => now()->subMinutes(1),
            'mercado_pago_id' => 'mp_pago_123'
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        // Mock response showing that the payment is still pending and has NOT expired in MercadoPago
        Http::fake([
            'api.mercadopago.com/v1/payments/mp_pago_123' => Http::response([
                'status' => 'pending',
                'date_of_expiration' => now()->addHours(24)->toIso8601String() // Expiración en el futuro
            ], 200)
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('pendiente', $pedido->fresh()->estado); // No debe cancelarse
        $this->assertEquals(10, $producto->fresh()->stock); // El stock se mantiene
    }

    /** @test */
    public function command_cancels_expired_pending_order_with_expired_payment_in_mercado_pago()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => now()->subMinutes(1),
            'mercado_pago_id' => 'mp_pago_expired'
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => $producto->precioUnitario
        ]);

        // Mock response showing payment expired in MercadoPago
        Http::fake([
            'api.mercadopago.com/v1/payments/mp_pago_expired' => Http::response([
                'status' => 'pending',
                'date_of_expiration' => now()->subMinutes(10)->toIso8601String() // Vencido
            ], 200)
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('cancelado', $pedido->fresh()->estado); // Se cancela
        $this->assertEquals(13, $producto->fresh()->stock); // Devuelve stock
    }

    /** @test */
    public function command_cancels_legacy_pending_order_older_than_96_hours()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => null, // Legacy
            'created_at' => now()->subHours(97)
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'precio_unitario' => $producto->precioUnitario
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        $this->assertEquals(14, $producto->fresh()->stock); // 10 + 4
    }

    /** @test */
    public function command_does_not_cancel_legacy_pending_order_under_96_hours()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => null, // Legacy
            'created_at' => now()->subHours(50) // Menos de 96 horas
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'precio_unitario' => $producto->precioUnitario
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('pendiente', $pedido->fresh()->estado); // No se cancela
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function command_run_twice_does_not_restore_stock_twice()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pendiente',
            'expira_at' => now()->subMinutes(1)
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        // Primera corrida
        Artisan::call('pedidos:liberar-vencidos');
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        $this->assertEquals(12, $producto->fresh()->stock);

        // Segunda corrida
        Artisan::call('pedidos:liberar-vencidos');
        $this->assertEquals(12, $producto->fresh()->stock); // Sigue igual
    }

    /** @test */
    public function command_does_not_touch_paid_or_cancelled_orders()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        
        $pedidoPagado = Pedido::factory()->create([
            'estado' => 'pagado',
            'expira_at' => now()->subHours(10)
        ]);
        DetallePedido::create([
            'pedido_id' => $pedidoPagado->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $pedidoCancelado = Pedido::factory()->create([
            'estado' => 'cancelado',
            'expira_at' => now()->subHours(10)
        ]);
        DetallePedido::create([
            'pedido_id' => $pedidoCancelado->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        $this->assertEquals('pagado', $pedidoPagado->fresh()->estado);
        $this->assertEquals('cancelado', $pedidoCancelado->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->stock); // No se alteró stock
    }

    /** @test */
    public function command_does_not_cancel_order_when_mp_search_fails_with_http_error()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado'          => 'pendiente',
            'expira_at'       => now()->subMinutes(1),
            'mercado_pago_id' => null,
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => $producto->precioUnitario,
        ]);

        // Simular que la búsqueda por external_reference falla con HTTP 500
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'message' => 'Internal Server Error'
            ], 500)
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        // Por seguridad (fail closed), el pedido NO debe cancelarse si MP falló con 500
        $this->assertEquals('pendiente', $pedido->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function command_does_not_cancel_order_when_mp_search_finds_active_approved_payment()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado'          => 'pendiente',
            'expira_at'       => now()->subMinutes(1),
            'mercado_pago_id' => null,
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => $producto->precioUnitario,
        ]);

        // Simular que la búsqueda por external_reference detecta un pago aprobado en MP
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [
                    [
                        'id'                 => 999111222,
                        'status'             => 'approved',
                        'external_reference' => (string) $pedido->id,
                    ]
                ]
            ], 200)
        ]);

        Artisan::call('pedidos:liberar-vencidos');

        // Al existir un pago aprobado detectado en la búsqueda, no debe cancelarse
        $this->assertEquals('pendiente', $pedido->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function dry_run_option_does_not_modify_order_state_stock_or_history()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado_pago' => 'pendiente',
            'expira_at'   => now()->subMinutes(5),
        ]);

        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 3,
            'precio_unitario' => $producto->precioUnitario,
        ]);

        $initialHistorialCount = \App\Models\PedidoHistorialEstado::count();

        Artisan::call('pedidos:liberar-vencidos', ['--dry-run' => true]);

        $this->assertEquals('pendiente', $pedido->fresh()->estado_pago);
        $this->assertEquals(10, $producto->fresh()->stock);
        $this->assertEquals($initialHistorialCount, \App\Models\PedidoHistorialEstado::count());

        $output = Artisan::output();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString("Pedido #{$pedido->id}", $output);
        $this->assertStringContainsString("Producto ID: {$producto->id}", $output);
    }

    /** @test */
    public function limit_option_restricts_number_of_processed_orders()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 10]);

        $pedido1 = Pedido::factory()->create(['estado_pago' => 'pendiente', 'expira_at' => now()->subMinutes(10)]);
        $pedido2 = Pedido::factory()->create(['estado_pago' => 'pendiente', 'expira_at' => now()->subMinutes(10)]);
        $pedido3 = Pedido::factory()->create(['estado_pago' => 'pendiente', 'expira_at' => now()->subMinutes(10)]);

        DetallePedido::create(['pedido_id' => $pedido1->id, 'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]);
        DetallePedido::create(['pedido_id' => $pedido2->id, 'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]);
        DetallePedido::create(['pedido_id' => $pedido3->id, 'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]);

        Artisan::call('pedidos:liberar-vencidos', ['--limit' => 1]);

        $expirados = Pedido::where('estado_pago', 'expirado')->count();
        $pendientes = Pedido::where('estado_pago', 'pendiente')->count();

        $this->assertEquals(1, $expirados);
        $this->assertEquals(2, $pendientes);
    }

    /** @test */
    public function without_limit_option_uses_default_limit()
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);

        $producto = Producto::factory()->create(['stock' => 100]);

        for ($i = 0; $i < 30; $i++) {
            $p = Pedido::factory()->create(['estado_pago' => 'pendiente', 'expira_at' => now()->subMinutes(10)]);
            DetallePedido::create(['pedido_id' => $p->id, 'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]);
        }

        Artisan::call('pedidos:liberar-vencidos');

        $expirados = Pedido::where('estado_pago', 'expirado')->count();
        $pendientes = Pedido::where('estado_pago', 'pendiente')->count();

        $this->assertEquals(25, $expirados);
        $this->assertEquals(5, $pendientes);
    }
}
