<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log del request recibido
        Log::info('Webhook MercadoPago recibido:', $request->all());

        // Early exit para notificaciones que no son pagos: no requieren firma ni procesamiento
        $topic = $request->query('topic') ?? $request->query('type')
              ?? $request->input('topic') ?? $request->input('type');

        if ($topic === 'merchant_order') {
            Log::info('Webhook: merchant_order ignorado', ['id' => $request->query('id')]);
            return response()->json(['message' => 'Evento ignorado'], 200);
        }

        // 1. Validar la firma del Webhook (HMAC-SHA256)
        if (!$this->isSignatureValid($request)) {
            Log::warning('Intento de acceso no autorizado al webhook de MercadoPago (firma inválida)', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
                'query' => $request->query(),
            ]);
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $topic = $request->input('topic') ?? $request->input('type');
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if ($topic !== 'payment' || !$paymentId) {
            return response()->json(['message' => 'Evento ignorado'], 200);
        }

        // Consultar el pago en MercadoPago
        $response = Http::withToken(config('mercadopago.access_token'))
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            Log::error('Error al consultar pago en MP', ['id' => $paymentId]);
            return response()->json(['error' => 'No se pudo verificar el pago'], 500);
        }

        $paymentData = $response->json();
        $status = $paymentData['status'];
        $pedidoId = $paymentData['external_reference'];

        // Transacción de actualización de pedido y stock
        DB::beginTransaction();
        try {
            $pedido = Pedido::where('id', $pedidoId)->lockForUpdate()->first();

            if (!$pedido) {
                DB::rollBack();
                Log::warning("Pedido no encontrado con ID externo: $pedidoId");
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            // Si el pedido ya está pagado o cancelado (estados terminales)
            // y el evento trae un estado no-terminal, bloqueamos la actualización.
            $terminalStates = ['pagado', 'cancelado'];
            $nonTerminalEventStatuses = ['pending', 'in_process', 'authorized', 'in_mediation'];

            if (in_array($pedido->estado, $terminalStates) && in_array($status, $nonTerminalEventStatuses)) {
                Log::warning("Intento de retroceso de estado ignorado en webhook de MercadoPago para el pedido #{$pedido->id}.", [
                    'pedido_id' => $pedido->id,
                    'estado_actual' => $pedido->estado,
                    'estado_evento' => $status,
                    'payment_id' => $paymentId
                ]);
                DB::commit();
                return response()->json(['message' => 'Intento de retroceso ignorado'], 200);
            }

            // Guardamos el mercado_pago_id
            $pedido->mercado_pago_id = $paymentId;

            // Procesar estados
            if ($status === 'approved') {
                // Idempotencia: Si ya estaba pagado, no hacemos nada más
                if ($pedido->estado !== 'pagado') {
                    $pedido->estado = 'pagado';
                    $pedido->save();
                    Log::info("Pedido {$pedido->id} actualizado a estado: pagado");
                }
            } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'])) {
                // Estados terminales que implican cancelación y reposición de stock
                // Idempotencia: Reponer stock solo si el estado actual no es 'cancelado'
                if ($pedido->estado !== 'cancelado') {
                    $pedido->estado = 'cancelado';
                    $pedido->save();

                    // Reponer el stock
                    $detalles = DetallePedido::where('pedido_id', $pedido->id)->get();
                    foreach ($detalles as $detalle) {
                        $producto = Producto::where('id', $detalle->producto_id)->lockForUpdate()->first();
                        if ($producto) {
                            $producto->stock += $detalle->cantidad;
                            $producto->save();
                        }
                    }
                    Log::info("Pedido {$pedido->id} actualizado a estado: cancelado (Pago fallido/reembolsado: {$status}). Stock repuesto.");
                }
            } elseif ($status === 'pending') {
                if ($pedido->estado !== 'pendiente') {
                    $pedido->estado = 'pendiente';
                    $pedido->save();
                    Log::info("Pedido {$pedido->id} actualizado a estado: pendiente");
                }
            } elseif (in_array($status, ['in_process', 'authorized', 'in_mediation'])) {
                // Loguear explícitamente estados intermedios
                Log::info("Webhook MercadoPago - Pago {$paymentId} para pedido {$pedido->id} en estado intermedio: {$status}");
                // Opcionalmente podemos mantenerlo en pendiente
                if ($pedido->estado !== 'pendiente') {
                    $pedido->estado = 'pendiente';
                    $pedido->save();
                }
            } else {
                Log::info("Webhook MercadoPago - Pago {$paymentId} para pedido {$pedido->id} con estado desconocido: {$status}");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al procesar webhook para pedido {$pedidoId}", [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Error interno al procesar el pago'], 500);
        }

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Valida la firma del webhook de MercadoPago
     */
    private function isSignatureValid(Request $request): bool
    {
        $signatureHeader = $request->header('x-signature');
        $requestId = $request->header('x-request-id') ?? '';
        $rawSecret = config('mercadopago.webhook_secret_token');
        $webhookSecret = is_string($rawSecret) ? trim($rawSecret) : '';

        // Falla cerrado: si no hay secreto configurado, rechazar
        if (!$webhookSecret) {
            Log::error('Webhook MercadoPago: No se ha configurado el secreto de validación (WEBHOOK_SECRET_TOKEN)');
            return false;
        }

        if (!$signatureHeader) {
            return false;
        }

        // Extraer ts y v1 del header x-signature
        // Formato: ts=TIMESTAMP,v1=HASH
        $parts = explode(',', $signatureHeader);
        $ts = null;
        $v1 = null;

        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) === 2) {
                $key = trim($keyValue[0]);
                $value = trim($keyValue[1]);
                if ($key === 'ts') {
                    $ts = $value;
                } elseif ($key === 'v1') {
                    $v1 = $value;
                }
            }
        }

        if (!$ts || !$v1) {
            return false;
        }

        // Obtener data.id (payment ID) de la consulta (query parameter) o fallback al body
        $dataId = $request->query('data_id')
               ?? $request->query('data.id')
               ?? $request->query('id')
               ?? data_get($request->all(), 'data.id');

        if (!$dataId) {
            return false;
        }

        // Construir string del manifiesto
        // Formato: id:{data_id};request-id:{request_id};ts:{ts};
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";

        // Calcular HMAC SHA256
        $calculatedSignature = hash_hmac('sha256', $manifest, $webhookSecret);

        // Comparación segura contra ataques de tiempo
        $isValid = hash_equals($calculatedSignature, $v1);

        if (!$isValid) {
            Log::error('Webhook Signature Mismatch Details (DIAGNOSIS)', [
                'data_id_used' => $dataId,
                'ts_used' => $ts,
                'request_id_used' => $requestId,
                'manifest_string' => $manifest,
                'calculated_hash' => $calculatedSignature,
                'received_v1' => $v1,
                'query_string_crudo' => $request->getQueryString(),
                'query_completo' => $request->query(),
                'secret_length' => strlen((string) $rawSecret),
                'secret_tiene_espacios' => (string) $rawSecret !== (string) $webhookSecret,
                'header_signature' => $signatureHeader,
            ]);
        }

        return $isValid;
    }
}
