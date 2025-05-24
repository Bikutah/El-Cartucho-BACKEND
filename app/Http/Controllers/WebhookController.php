<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log del request recibido
        Log::info('Webhook MercadoPago recibido:', $request->all());

        $topic = $request->input('topic') ?? $request->input('type');
        $paymentId = $request->input('data.id') ?? $request->input('id');

        // 🧪 BLOQUE DE SIMULACIÓN (cuando id = 999999)
        if ($paymentId == 999999) {
            $pedido = Pedido::find(1); // Simulamos sobre el pedido 1

            if (!$pedido) {
                return response()->json(['error' => 'Pedido no encontrado (simulado)'], 404);
            }

            $pedido->estado = 'pagado';
            $pedido->mercado_pago_id = $paymentId;
            $pedido->save();

            Log::info("✅ Simulación: Pedido {$pedido->id} actualizado a 'pagado' desde webhook mock");

            return response()->json([
                'message' => 'Simulación exitosa',
                'pedido_id' => $pedido->id,
                'estado' => $pedido->estado
            ]);
        }

        // ⚠️ Si no es mock, va al flujo real
        if ($topic !== 'payment' || !$paymentId) {
            return response()->json(['message' => 'Evento ignorado'], 200);
        }

        $response = Http::withToken(env('MERCADO_PAGO_ACCESS_TOKEN'))
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            Log::error('Error al consultar pago en MP', ['id' => $paymentId]);
            return response()->json(['error' => 'No se pudo verificar el pago'], 500);
        }

        $paymentData = $response->json();
        $status = $paymentData['status'];
        $pedidoId = $paymentData['external_reference'];

        $pedido = Pedido::find($pedidoId);

        if (!$pedido) {
            Log::warning("Pedido no encontrado con ID externo: $pedidoId");
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        if ($status === 'approved') {
            $pedido->estado = 'pagado';
        } elseif ($status === 'rejected') {
            $pedido->estado = 'cancelado';
        } elseif ($status === 'pending') {
            $pedido->estado = 'pendiente';
        }

        $pedido->mercado_pago_id = $paymentId;
        $pedido->save();

        Log::info("Pedido {$pedido->id} actualizado a estado: {$pedido->estado}");

        return response()->json(['message' => 'OK'], 200);
    }

}
