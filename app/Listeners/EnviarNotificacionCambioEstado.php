<?php

namespace App\Listeners;

use App\Events\PedidoEstadoActualizado;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class EnviarNotificacionCambioEstado
{
    // ─── Tabla de transiciones que disparan notificación ──────────────────────
    //
    // Clave: "{tipo}.{estado_nuevo}"
    // Valor: callable(event) => [title, body] | null (null = no notifica)
    private const TRANSICIONES = [
        'pago.pagado' => [
            'titulo' => '¡Pago aprobado!',
            'body'   => 'Tu pedido #{id} está confirmado. ¡Gracias por tu compra!',
        ],
        'pago.rechazado' => [
            'titulo' => 'Pago rechazado',
            'body'   => 'Tu pago no se pudo procesar. Podés reintentarlo desde la app.',
        ],
        'pago.reembolsado' => [
            'titulo' => 'Reembolso procesado',
            'body'   => 'Tu reembolso del pedido #{id} ya fue procesado.',
        ],
        // pago.expirado se maneja con condición especial (solo origen 'cliente')
        'envio.enviado' => [
            'titulo' => '¡Tu pedido va en camino! 📦',
            'body'   => 'Tu pedido #{id} ya fue despachado.',
        ],
        'envio.entregado' => [
            'titulo' => 'Pedido entregado ✅',
            'body'   => 'Tu pedido #{id} fue entregado. ¡Que lo disfrutes!',
        ],
    ];

    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function handle(PedidoEstadoActualizado $event): void
    {
        // ── CRÍTICO: el push JAMÁS puede romper el cambio de estado ──────────
        try {
            $this->despachar($event);
        } catch (\Throwable $e) {
            Log::error('[PushNotif] Error al enviar notificación de estado', [
                'pedido_id'     => $event->pedido->id,
                'tipo'          => $event->tipo,
                'estado_nuevo'  => $event->estadoNuevo,
                'origen'        => $event->origen,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }

    private function despachar(PedidoEstadoActualizado $event): void
    {
        // 1. Determinar el userId dueño del pedido
        $userId = $event->pedido->user_id;
        if (!$userId) {
            return; // Pedido sin usuario asociado → no hay a quién notificar
        }

        // 2. Determinar mensaje según tabla de transiciones
        $clave = "{$event->tipo}.{$event->estadoNuevo}";

        // Caso especial: pago.expirado solo cuando el origen es 'cliente'
        if ($clave === 'pago.expirado') {
            if ($event->origen !== 'cliente') {
                return; // El cron u otros orígenes no despiertan al usuario
            }
            $titulo = 'Pedido cancelado';
            $body   = "Cancelaste tu pedido #{$event->pedido->id}. Podés armar uno nuevo cuando quieras.";
        } elseif (isset(self::TRANSICIONES[$clave])) {
            $def   = self::TRANSICIONES[$clave];
            $titulo = $def['titulo'];
            $body   = str_replace('{id}', (string) $event->pedido->id, $def['body']);
        } else {
            return; // Default deny: transición no listada → no notifica
        }

        // 3. Armar payload (mismo formato que el broadcast admin)
        $payload = json_encode([
            'title' => $titulo,
            'body'  => $body,
            'url'   => "/mis-pedidos/{$event->pedido->id}",
            'icon'  => '/icon.svg',
            'badge' => '/favicon.ico',
        ]);

        // 4. Enviar
        $this->pushService->sendToUser($userId, $payload);
    }
}
