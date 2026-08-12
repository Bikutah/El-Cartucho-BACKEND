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
        Config::set('services.mercadopago.webhook_secret', 'test_secret_token');
        Config::set('services.mercadopago.access_token', 'test_access_token');
        Config::set('services.mercadopago.front_url', 'http://localhost:3000');
    }

    private function getSignatureHeader($dataId, $requestId, $ts, $secret = 'test_secret_token')
    {
        $cleanDataId = strtolower($dataId);
        $manifest = "id:{$cleanDataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secret);
        return "ts={$ts},v1={$v1}";
    }

    /** @test */
    public function test_1_webhook_with_new_format_and_valid_signature_updates_order_to_paid()
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

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'approved',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [
            'data' => ['id' => $paymentId]
        ], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals($paymentId, $pedido->fresh()->mercado_pago_id);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function test_2_webhook_with_new_format_and_invalid_signature_returns_401_without_modifying_order()
    {
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=123456&type=payment', [
            'data' => ['id' => '123456']
        ], [
            'x-signature' => 'ts=123,v1=invalidhash',
            'x-request-id' => 'req123'
        ]);

        $response->assertStatus(401);
        $this->assertEquals('pendiente', $pedido->fresh()->estado);
    }

    /** @test */
    public function test_3_legacy_webhook_without_data_id_returns_200_without_validation_or_modification()
    {
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        // Notificación legacy: solo incluye ?id=123456&topic=payment, no incluye data_id
        $response = $this->postJson('/ed/webhook/mercadopago?id=123456&topic=payment', [
            'id' => '123456'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notificación ignorada: falta data_id o x-signature']);
        $this->assertEquals('pendiente', $pedido->fresh()->estado);
    }

    /** @test */
    public function test_4_webhook_without_signature_header_returns_200_without_modifying_order()
    {
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=123456&type=payment', [
            'data' => ['id' => '123456']
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notificación ignorada: falta data_id o x-signature']);
        $this->assertEquals('pendiente', $pedido->fresh()->estado);
    }

    /** @test */
    public function test_5a_repeated_approved_notification_returns_200_without_duplicate_db_write()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente']);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '111222333';
        $requestId = 'req_id_dup_app';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'approved',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        // Primera notificación
        $res1 = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado);

        // Segunda notificación duplicada
        $res2 = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['message' => 'Notificación duplicada ya procesada']);
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function test_5b_repeated_rejected_notification_returns_200_without_duplicate_stock_restoration()
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
        $requestId = 'req_id_dup_rej';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'rejected',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        // Primer envío
        $res1 = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        // Stock repuesto una vez: 10 + 3 = 13
        $this->assertEquals(13, $producto->fresh()->stock);

        // Segundo envío
        $res2 = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['message' => 'Notificación duplicada ya procesada']);
        // Stock no debe reponerse por segunda vez
        $this->assertEquals(13, $producto->fresh()->stock);
    }

    /** @test */
    public function test_5c_late_approved_notification_for_cancelled_order_is_ignored()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'cancelado', 'mercado_pago_id' => null]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentId = '555666777';
        $requestId = 'req_id_late_app';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status' => 'approved',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Transición no permitida desde cancelado']);
        // Debe permanecer cancelado
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        // mercado_pago_id no debe ser sobrescrito
        $this->assertNull($pedido->fresh()->mercado_pago_id);
    }

    /** @test */
    public function test_5d_paid_order_receiving_rejected_notification_from_different_payment_is_ignored()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create([
            'estado' => 'pagado',
            'mercado_pago_id' => 'payment_A'
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $paymentB = 'payment_B';
        $requestId = 'req_id_different_payment';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentB, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentB}" => Http::response([
                'status' => 'rejected',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentB . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Transición no permitida desde pagado para este pago']);
        // Permanece pagado y mercado_pago_id sigue siendo payment_A
        $this->assertEquals('pagado', $pedido->fresh()->estado);
        $this->assertEquals('payment_A', $pedido->fresh()->mercado_pago_id);
        // El stock no se altera
        $this->assertEquals(10, $producto->fresh()->stock);
    }

    /** @test */
    public function test_5e_refunded_notification_for_matching_payment_on_paid_order_transitions_to_cancelled_and_restores_stock()
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $paymentA = 'payment_A';
        $pedido = Pedido::factory()->create([
            'estado' => 'pagado',
            'mercado_pago_id' => $paymentA
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'precio_unitario' => $producto->precioUnitario
        ]);

        $requestId = 'req_id_refund';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentA, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentA}" => Http::response([
                'status' => 'refunded',
                'external_reference' => (string)$pedido->id
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentA . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $this->assertEquals('cancelado', $pedido->fresh()->estado);
        $this->assertEquals($paymentA, $pedido->fresh()->mercado_pago_id);
        // Stock repuesto: 10 + 4 = 14
        $this->assertEquals(14, $producto->fresh()->stock);
    }

    /** @test */
    public function webhook_returns_500_when_mercadopago_api_returns_401_or_403()
    {
        $paymentId = '888999000';
        $requestId = 'req_id_auth_fail';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'message' => 'Invalid token'
            ], 401)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function webhook_returns_200_when_mercadopago_api_returns_404()
    {
        $paymentId = '000000000';
        $requestId = 'req_id_not_found';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'message' => 'Payment not found'
            ], 404)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature' => $signature,
            'x-request-id' => $requestId
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Pago no encontrado en MercadoPago']);
    }

    /** @test */
    public function guard_de_pagado_con_data_id_distinto_sigue_funcionando()
    {
        $pedido = Pedido::factory()->create([
            'estado_pago'     => 'pagado',
            'mercado_pago_id' => '111111',
        ]);

        $paymentId = '999999';
        $requestId = 'req_id_diff';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status'             => 'rejected',
                'external_reference' => (string)$pedido->id,
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature'  => $signature,
            'x-request-id' => $requestId,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado_pago);
        $this->assertEquals('111111', $pedido->fresh()->mercado_pago_id);
    }

    /** @test */
    public function un_pago_aprobado_genera_historial_con_origen_webhook()
    {
        $pedido = Pedido::factory()->create(['estado_pago' => 'pendiente']);

        $paymentId = '888888';
        $requestId = 'req_id_hist';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status'             => 'approved',
                'external_reference' => (string)$pedido->id,
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature'  => $signature,
            'x-request-id' => $requestId,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado_pago);

        $this->assertDatabaseHas('pedido_historial_estados', [
            'pedido_id'    => $pedido->id,
            'tipo'         => 'pago',
            'estado_nuevo' => 'pagado',
            'origen'       => 'webhook',
        ]);
    }

    /** @test */
    public function pedido_pagado_con_mismo_payment_id_recibe_rejected_es_ignorado_y_mantiene_stock()
    {
        $producto = Producto::factory()->create(['stock' => 5]);
        $pedido = Pedido::factory()->create([
            'estado_pago'     => 'pagado',
            'mercado_pago_id' => '777777',
        ]);
        DetallePedido::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 100,
        ]);

        $paymentId = '777777';
        $requestId = 'req_id_same_rej';
        $ts = time();
        $signature = $this->getSignatureHeader($paymentId, $requestId, $ts);

        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'status'             => 'rejected',
                'external_reference' => (string)$pedido->id,
            ], 200)
        ]);

        $response = $this->postJson('/ed/webhook/mercadopago?data_id=' . $paymentId . '&type=payment', [], [
            'x-signature'  => $signature,
            'x-request-id' => $requestId,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('pagado', $pedido->fresh()->estado_pago);
        $this->assertEquals('777777', $pedido->fresh()->mercado_pago_id);
        $this->assertEquals(5, $producto->fresh()->stock); // Stock intacto
    }
}
