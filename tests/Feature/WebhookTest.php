<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Definir secreto de prueba
        Config::set('mercadopago.webhook_secret_token', 'test_secret_token');
        Config::set('mercadopago.access_token', 'test_access_token');
    }

    private function getSignatureHeader($dataId, $requestId, $ts, $secret = 'test_secret_token')
    {
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secret);
        return "ts={$ts},v1={$v1}";
    }

    /** @test */
    public function webhook_fails_without_signature_header()
    {
        $response = $this->postJson('/ed/webhook/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123456']
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function webhook_fails_with_invalid_signature_header()
    {
        $response = $this->postJson('/ed/webhook/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123456']
        ], [
            'x-signature' => 'ts=123,v1=invalidhash',
            'x-request-id' => 'req123'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function webhook_updates_order_to_paid_on_approved_status()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '123456789';
        $requestId = 'req_id_1';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        // Fake response from MP GET payment
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'approved',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals($paymentId, $pedido->fresh()->mercado_pago_id);
        
        // El stock no debe alterarse (se descontó al crear, no se repone en pago aprobado)
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_restores_stock_on_terminal_failure_status()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '987654321';
        $requestId = 'req_id_2';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'rejected',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        // Debe reponerse el stock: 10 + 3 = 13
        $this->assertEquals(13, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_restores_stock_only_once_on_duplicate_terminal_failure_events()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '987654321';
        $requestId = 'req_id_2';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'rejected',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        // Primer envío
        $response1 = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $response1->assertStatus(200);
        $this->assertEquals(13, $producto->fresh()->stock);

        // Segundo envío
        $response2 = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $response2->assertStatus(200);
        
        // El stock debe seguir siendo 13, no debe reponerse doble
        $this->assertEquals(13, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_does_not_modify_stock_when_already_paid_order_receives_approved_again()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pagado']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '111222333';
        $requestId = 'req_id_3';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'approved',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        // El stock debe seguir en 10 (no se altera ni decrementa doble)
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_pending_does_not_revert_cancelled_order()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'cancelado']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '444555666';
        $requestId = 'req_id_4';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'pending',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        // Debe seguir cancelado
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        // Stock no debe reponerse doble ni modificarse
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_pending_does_not_revert_paid_order()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pagado']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '777888999';
        $requestId = 'req_id_5';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'pending',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        // Debe seguir pagado
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_refunded_transitions_paid_order_to_cancelled_and_restores_stock()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pagado']);
        
        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '11223344';
        $requestId = 'req_id_6';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'refunded',
                'external_reference' => $pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data.id=' . $paymentId, [
            'type' => 'payment',
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        // Debe pasar a cancelado
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        // Debe reponerse stock: 10 + 4 = 14
        $this->assertEquals(14, $producto->fresh()->stock);
    }
}
