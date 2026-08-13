<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Endpoint protegido para disparar la liberación de pedidos vencidos.
     * Invocado por Vercel Cron vía GET con header `Authorization: Bearer <CRON_SECRET>`.
     */
    public function liberarPedidosVencidos(Request $request)
    {
        $secret = config('services.mercadopago.cron_secret');
        $authHeader = $request->header('Authorization') ?? '';

        if (empty($secret) || empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $providedSecret = substr($authHeader, 7);

        if (empty($providedSecret) || !hash_equals((string) $secret, (string) $providedSecret)) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        Artisan::call('pedidos:liberar-vencidos');
        $output = Artisan::output();

        Log::info('CronController: Ejecutada liberación de pedidos vencidos vía HTTP GET cron');

        return response()->json([
            'message' => 'Proceso de liberación completado',
            'output'  => trim($output),
        ], 200);
    }
}
