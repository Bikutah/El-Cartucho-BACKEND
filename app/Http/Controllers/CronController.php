<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Endpoint protegido para disparar la liberación de pedidos vencidos.
     */
    public function liberarPedidosVencidos(Request $request)
    {
        $secret = env('CRON_SECRET');
        $headerSecret = $request->header('x-cron-secret');

        if (empty($secret) || empty($headerSecret) || !hash_equals((string) $secret, (string) $headerSecret)) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        Artisan::call('pedidos:liberar-vencidos');
        $output = Artisan::output();

        Log::info('CronController: Ejecutada liberación de pedidos vencidos vía endpoint HTTP cron');

        return response()->json([
            'message' => 'Proceso de liberación completado',
            'output'  => trim($output),
        ], 200);
    }
}
