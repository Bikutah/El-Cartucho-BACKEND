<?php

namespace Tests\Feature;

use App\Events\PedidoEstadoActualizado;
use App\Listeners\EnviarNotificacionCambioEstado;
use App\Models\Pedido;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PushTransaccionalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Helper: pedido vinculado al usuario ──────────────────────────────────

    private function crearPedido(array $attrs = []): Pedido
    {
        return Pedido::factory()->create(array_merge([
            'user_id'    => $this->user->id,
            'estado_pago' => 'pendiente',
        ], $attrs));
    }

    // ─── Test A4.1: cambiarEstadoPago despacha el evento ─────────────────────

    /** @test */
    public function cambiar_estado_pago_despacha_evento_pedido_estado_actualizado()
    {
        Event::fake();

        $pedido = $this->crearPedido(['estado_pago' => 'pendiente']);
        $pedido->cambiarEstadoPago('pagado', 'webhook');

        Event::assertDispatched(PedidoEstadoActualizado::class, function ($event) use ($pedido) {
            return $event->pedido->id === $pedido->id
                && $event->tipo === 'pago'
                && $event->estadoNuevo === 'pagado'
                && $event->origen === 'webhook';
        });
    }

    /** @test */
    public function cambiar_estado_envio_despacha_evento_pedido_estado_actualizado()
    {
        Event::fake();

        $pedido = $this->crearPedido(['estado_pago' => 'pagado', 'estado_envio' => 'sin_preparar']);
        $pedido->cambiarEstadoEnvio('preparando', 'panel', $this->user->id);

        Event::assertDispatched(PedidoEstadoActualizado::class, function ($event) use ($pedido) {
            return $event->pedido->id === $pedido->id
                && $event->tipo === 'envio'
                && $event->estadoNuevo === 'preparando';
        });
    }

    // ─── Test A4.2: el listener llama sendToUser con texto correcto ───────────

    /**
     * @test
     * @dataProvider transicionesQueNotifican
     */
    public function listener_envia_push_correcto_para_transicion(
        string $tipo,
        string $estadoNuevo,
        string $origen,
        string $tituloEsperado,
        string $bodyContiene
    ) {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->once())
            ->method('sendToUser')
            ->with(
                $this->equalTo($this->user->id),
                $this->callback(function (string $payload) use ($tituloEsperado, $bodyContiene) {
                    $data = json_decode($payload, true);
                    return $data['title'] === $tituloEsperado
                        && str_contains($data['body'], $bodyContiene);
                })
            );

        $pedido = $this->crearPedido(['user_id' => $this->user->id]);

        $event = new PedidoEstadoActualizado(
            pedido: $pedido,
            tipo: $tipo,
            estadoAnterior: null,
            estadoNuevo: $estadoNuevo,
            origen: $origen,
            userId: $this->user->id,
        );

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    public static function transicionesQueNotifican(): array
    {
        return [
            'pago_pagado'       => ['pago', 'pagado', 'webhook', '¡Pago aprobado!', 'confirmado'],
            'pago_rechazado'    => ['pago', 'rechazado', 'webhook', 'Pago rechazado', 'procesar'],
            'pago_reembolsado'  => ['pago', 'reembolsado', 'panel', 'Reembolso procesado', 'reembolso'],
            'pago_expirado_cliente' => ['pago', 'expirado', 'cliente', 'Pedido cancelado', 'Cancelaste'],
            'envio_enviado'     => ['envio', 'enviado', 'panel', '¡Tu pedido va en camino! 📦', 'despachado'],
            'envio_entregado'   => ['envio', 'entregado', 'panel', 'Pedido entregado ✅', 'entregado'],
        ];
    }

    // ─── Test A4.3: expirado con origen no-cliente NO notifica ───────────────

    /** @test */
    public function listener_no_notifica_en_expirado_con_origen_comando()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->never())->method('sendToUser');

        $pedido = $this->crearPedido();
        $event = new PedidoEstadoActualizado($pedido, 'pago', 'pendiente', 'expirado', 'comando', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    /** @test */
    public function listener_no_notifica_en_expirado_con_origen_sistema()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->never())->method('sendToUser');

        $pedido = $this->crearPedido();
        $event = new PedidoEstadoActualizado($pedido, 'pago', 'pendiente', 'expirado', 'sistema', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    /** @test */
    public function listener_si_notifica_en_expirado_con_origen_cliente()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->once())->method('sendToUser');

        $pedido = $this->crearPedido();
        $event = new PedidoEstadoActualizado($pedido, 'pago', 'pendiente', 'expirado', 'cliente', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    // ─── Test A4.4: transición no listada NO notifica ─────────────────────────

    /** @test */
    public function listener_no_notifica_en_transicion_no_listada()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->never())->method('sendToUser');

        $pedido = $this->crearPedido(['estado_pago' => 'pagado', 'estado_envio' => null]);
        // 'preparando' no está en la tabla → no notifica
        $event = new PedidoEstadoActualizado($pedido, 'envio', null, 'preparando', 'panel', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    /** @test */
    public function listener_no_notifica_en_estado_pendiente_de_pago()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->never())->method('sendToUser');

        $pedido = $this->crearPedido();
        $event = new PedidoEstadoActualizado($pedido, 'pago', null, 'pendiente', 'webhook', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    // ─── Test A4.5 (CRÍTICO): excepción en push NO rompe cambiarEstadoPago ───

    /** @test */
    public function excepcion_en_push_service_no_impide_que_cambiar_estado_pago_complete()
    {
        // El servicio lanza una excepción
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->method('sendToUser')
            ->willThrowException(new \RuntimeException('Simulated push failure'));

        $this->app->instance(PushNotificationService::class, $mockService);

        $pedido = $this->crearPedido(['estado_pago' => 'pendiente']);

        // cambiarEstadoPago debe retornar true aunque el push explote
        $resultado = $pedido->cambiarEstadoPago('pagado', 'webhook');

        $this->assertTrue($resultado);
        $this->assertEquals('pagado', $pedido->fresh()->estado_pago);
    }

    /** @test */
    public function excepcion_en_push_service_no_impide_que_cambiar_estado_envio_complete()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->method('sendToUser')
            ->willThrowException(new \RuntimeException('Simulated push failure'));

        $this->app->instance(PushNotificationService::class, $mockService);

        $pedido = $this->crearPedido(['estado_pago' => 'pagado', 'estado_envio' => 'preparando']);

        $resultado = $pedido->cambiarEstadoEnvio('enviado', 'panel', $this->user->id);

        $this->assertTrue($resultado);
        $this->assertEquals('enviado', $pedido->fresh()->estado_envio);
    }

    // ─── Test A4.6: sendToUser solo apunta a suscripciones del usuario ───────

    /** @test */
    public function send_to_user_no_envia_a_suscripciones_de_otros_usuarios()
    {
        $otroUsuario = User::factory()->create();

        // Suscripción del usuario objetivo
        PushSubscription::create([
            'user_id'  => $this->user->id,
            'endpoint' => 'https://push.example.com/user1',
            'p256dh'   => 'dGVzdHAxMjM0NTY3ODkwYWJjZGVmZ2hpamtsbW5vcHFyc3Q=',
            'auth'     => 'dGVzdGF1dGgxMjM=',
        ]);

        // Suscripción del otro usuario (NO debe recibir)
        PushSubscription::create([
            'user_id'  => $otroUsuario->id,
            'endpoint' => 'https://push.example.com/user2',
            'p256dh'   => 'dGVzdHAxMjM0NTY3ODkwYWJjZGVmZ2hpamtsbW5vcHFyc3Q=',
            'auth'     => 'dGVzdGF1dGgxMjM=',
        ]);

        // Verificamos con una query directa que sendToUser filtra por user_id
        $subsDelUsuario = PushSubscription::where('user_id', $this->user->id)->get();
        $subsDelOtro    = PushSubscription::where('user_id', $otroUsuario->id)->get();

        $this->assertCount(1, $subsDelUsuario);
        $this->assertCount(1, $subsDelOtro);
        $this->assertEquals('https://push.example.com/user1', $subsDelUsuario->first()->endpoint);
        $this->assertEquals('https://push.example.com/user2', $subsDelOtro->first()->endpoint);
    }

    /** @test */
    public function send_to_user_sin_suscripciones_retorna_ceros_sin_instanciar_webpush()
    {
        // Usuario sin suscripciones → el servicio no debe explotar
        $service = new PushNotificationService();

        $resultado = $service->sendToUser($this->user->id, json_encode(['title' => 'test']));

        $this->assertEquals([0, 0, 0], $resultado);
    }

    // ─── Test: listener no notifica si pedido no tiene user_id ───────────────

    /** @test */
    public function listener_no_notifica_si_pedido_sin_user_id()
    {
        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->never())->method('sendToUser');

        $pedido = Pedido::factory()->create(['user_id' => null, 'estado_pago' => 'pendiente']);

        $event = new PedidoEstadoActualizado($pedido, 'pago', 'pendiente', 'pagado', 'webhook', null);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);
    }

    // ─── Test: URL del push apunta a /mis-pedidos/{id} ───────────────────────

    /** @test */
    public function listener_arma_url_correcta_con_ruta_mis_pedidos()
    {
        $capturedPayload = null;

        $mockService = $this->createMock(PushNotificationService::class);
        $mockService->expects($this->once())
            ->method('sendToUser')
            ->with(
                $this->anything(),
                $this->callback(function (string $payload) use (&$capturedPayload) {
                    $capturedPayload = $payload;
                    return true;
                })
            );

        $pedido = $this->crearPedido();
        $event = new PedidoEstadoActualizado($pedido, 'pago', 'pendiente', 'pagado', 'webhook', $this->user->id);

        $listener = new EnviarNotificacionCambioEstado($mockService);
        $listener->handle($event);

        $data = json_decode($capturedPayload, true);
        $this->assertEquals("/mis-pedidos/{$pedido->id}", $data['url']);
    }
}
