<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Opcional: registrar el request
        \Log::info('Webhook recibido', $request->all());

        // Validar campos
        $validated = $request->validate([
            'id' => 'required|string',
            'topic' => 'required|string',
        ]);

        if ($validated['topic'] === 'payment') {
            return response()->json(['message' => 'Se logró'], 200);
        }

        return response()->json(['message' => 'Topic no válido'], 400);
    }

}