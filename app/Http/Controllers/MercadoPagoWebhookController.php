<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Guardás el payload recibido para depuración
        Log::info('Webhook de Mercado Pago recibido:', $request->all());

        // Accedés a los datos que te interesan
        $tipo = $request->input('type');
        $data = $request->input('data.id');

        // Acá podrías manejar distintos eventos: payment, merchant_order, etc.
        if ($tipo === 'payment') {
            Log::channel('database')->info('Webhook de pago recibido', [
                'payment_id' => $request->input('data.id'),
                'topic' => $request->input('type') ?? $request->input('topic'),
                'payload' => $request->all()
            ]);
        }        

        return response()->json(['status' => 'ok']);
    }
}

