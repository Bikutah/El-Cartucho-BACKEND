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
        Log::info('Webhook MercadoPago recibido:', $request->all());

        // 1. Leer data_id exclusivamente desde query params (?data_id=X)
        $dataId = $request->query('data_id');
        $signatureHeader = $request->header('x-signature');

        // Rule 2: Si falta data_id o x-signature -> responder 200 (notificación legacy o sin firma)
        if (!$dataId || !$signatureHeader) {
            return response()->json(['message' => 'Notificación ignorada: falta data_id o x-signature'], 200);
        }

        // 2. Validar la firma del Webhook (HMAC-SHA256)
        if (!$this->isSignatureValid($request, $dataId, $signatureHeader)) {
            return response()->json(['error' => 'No autorizado: firma inválida'], 401);
        }

        // 3. Consultar el pago en MercadoPago
        $accessToken = config('services.mercadopago.access_token');
        $response = Http::withToken($accessToken)
            ->get("https://api.mercadopago.com/v1/payments/{$dataId}");

        if ($response->status() === 401 || $response->status() === 403) {
            Log::error("Error de autenticación al consultar pago en MP (HTTP {$response->status()})", [
                'data_id' => $dataId,
            ]);
            return response()->json(['error' => 'Error de autenticación al consultar pago en MercadoPago'], 500);
        }

        if ($response->status() === 404) {
            Log::warning("Pago no encontrado en MercadoPago (404)", ['data_id' => $dataId]);
            return response()->json(['message' => 'Pago no encontrado en MercadoPago'], 200);
        }

        if (!$response->successful()) {
            Log::error("Error al consultar pago en MP (HTTP {$response->status()})", ['data_id' => $dataId]);
            return response()->json(['error' => 'No se pudo verificar el pago'], 500);
        }

        $paymentData = $response->json();
        $status = $paymentData['status'] ?? null;
        $pedidoId = $paymentData['external_reference'] ?? null;

        if (!$pedidoId) {
            Log::warning("Pago sin referencia externa de pedido", ['data_id' => $dataId]);
            return response()->json(['message' => 'Pago sin referencia de pedido'], 200);
        }

        $targetState = match ($status) {
            'approved' => 'pagado',
            'rejected', 'cancelled', 'refunded', 'charged_back' => 'cancelado',
            'pending', 'in_process', 'authorized', 'in_mediation' => 'pendiente',
            default => null,
        };

        // 4. Transacción de actualización de pedido y stock con idempotencia y guards de transición
        DB::beginTransaction();
        try {
            $pedido = Pedido::where('id', $pedidoId)->lockForUpdate()->first();

            if (!$pedido) {
                DB::rollBack();
                Log::warning("Pedido no encontrado con ID externo: {$pedidoId}");
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            // Idempotencia: Si ya tiene el mismo mercado_pago_id y el mismo estado -> responder 200 sin escribir
            if ($pedido->mercado_pago_id === (string)$dataId && $pedido->estado === $targetState) {
                DB::commit();
                return response()->json(['message' => 'Notificación duplicada ya procesada'], 200);
            }

            // Guards de transiciones válidas:
            // 1) Desde 'cancelado' no se sale bajo ninguna circunstancia (y NO sobrescribir mercado_pago_id)
            if ($pedido->estado === 'cancelado') {
                DB::commit();
                Log::warning("Transición de estado ignorada para pedido cancelado #{$pedido->id}.", [
                    'pedido_id' => $pedido->id,
                    'estado_actual' => 'cancelado',
                    'estado_evento' => $status,
                    'payment_id' => $dataId,
                ]);
                return response()->json(['message' => 'Transición no permitida desde cancelado'], 200);
            }

            // 2) Desde 'pagado' solo se permite pasar a 'cancelado' si $dataId coincide con mercado_pago_id del pedido y el status es refunded, charged_back o cancelled
            if ($pedido->estado === 'pagado') {
                $esMismoPago = ($pedido->mercado_pago_id !== null && (string)$pedido->mercado_pago_id === (string)$dataId);
                $esReembolsoValido = $esMismoPago && in_array($status, ['refunded', 'charged_back', 'cancelled']);

                if (!$esReembolsoValido) {
                    DB::commit();
                    Log::warning("Notificación de pago ignorada para pedido ya pagado #{$pedido->id}.", [
                        'pedido_id' => $pedido->id,
                        'estado_actual' => 'pagado',
                        'estado_evento' => $status,
                        'payment_id_actual' => $pedido->mercado_pago_id,
                        'payment_id_evento' => $dataId,
                    ]);
                    return response()->json(['message' => 'Transición no permitida desde pagado para este pago'], 200);
                }
            }

            // Actualizar mercado_pago_id SOLO tras superar guards válidos
            $pedido->mercado_pago_id = (string)$dataId;

            // Procesar cambio de estado
            if ($targetState === 'pagado') {
                if ($pedido->estado !== 'pagado') {
                    $pedido->estado = 'pagado';
                    $pedido->save();
                    Log::info("Pedido {$pedido->id} actualizado a estado: pagado");
                }
            } elseif ($targetState === 'cancelado') {
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
            } elseif ($targetState === 'pendiente') {
                if ($pedido->estado !== 'pendiente') {
                    $pedido->estado = 'pendiente';
                    $pedido->save();
                    Log::info("Pedido {$pedido->id} actualizado a estado: pendiente");
                }
            } else {
                Log::info("Webhook MercadoPago - Pago {$dataId} para pedido {$pedido->id} con estado no mapeado: {$status}");
                $pedido->save();
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
    private function isSignatureValid(Request $request, string $dataId, string $signatureHeader): bool
    {
        $requestId = $request->header('x-request-id') ?? '';
        $webhookSecret = config('services.mercadopago.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Webhook MercadoPago: No se ha configurado el secreto de validación (WEBHOOK_SECRET_TOKEN)');
            return false;
        }

        // Extraer ts y v1 del header x-signature (formato: ts=TIMESTAMP,v1=HASH)
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

        if (!$ts || !$v1 || !$requestId) {
            $cleanDataId = strtolower($dataId);
            $manifest = "id:{$cleanDataId};request-id:{$requestId};ts:{$ts};";
            Log::warning('Firma de webhook MercadoPago inválida (header malformado o sin request-id)', [
                'manifest' => $manifest,
                'v1' => $v1,
            ]);
            return false;
        }

        $cleanDataId = strtolower($dataId);
        $manifest = "id:{$cleanDataId};request-id:{$requestId};ts:{$ts};";

        $calculatedSignature = hash_hmac('sha256', $manifest, $webhookSecret);

        $isValid = hash_equals($calculatedSignature, $v1);

        if (!$isValid) {
            Log::warning('Firma de webhook MercadoPago inválida', [
                'manifest' => $manifest,
                'v1' => $v1,
            ]);
        }

        return $isValid;
    }
}
