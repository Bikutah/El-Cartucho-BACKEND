<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verificar token de seguridad
        $authorization = $request->header('Authorization');
        $expectedToken = 'Bearer ' . config('services.webhook.token');

        if ($authorization !== $expectedToken) {
            Log::warning('🔐 Webhook rechazado por token inválido', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Log de recepción general
        Log::info('✅ Webhook de Mercado Pago recibido:', $request->all());

        // Identificar tipo de evento y datos
        $tipo = $request->input('type') ?? $request->input('topic');
        $data = $request->input('data.id') ?? $request->input('id');

        // Manejo específico para 'payment'
        if ($tipo === 'payment') {
            Log::channel('database')->info('📦 Webhook de pago recibido', [
                'payment_id' => $data,
                'topic' => $tipo,
                'payload' => $request->all()
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
