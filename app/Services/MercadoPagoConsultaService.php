<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MercadoPagoConsultaService
{
    /**
     * Consulta la API de Mercado Pago y determina si existe algún pago activo
     * (approved, authorized, in_process, o pending no vencido) para el pedido.
     */
    public function tienePagoVivo(Pedido $pedido): bool
    {
        $accessToken = config('services.mercadopago.access_token');
        if (!$accessToken) {
            Log::error('MercadoPagoConsultaService: access_token no configurado');
            return false;
        }

        // 1. Si el pedido ya tiene un mercado_pago_id asociado, consultar ese pago específico
        if ($pedido->mercado_pago_id) {
            try {
                $response = Http::timeout(5)
                    ->withToken($accessToken)
                    ->get("https://api.mercadopago.com/v1/payments/{$pedido->mercado_pago_id}");

                if ($response->successful()) {
                    $paymentData = $response->json();
                    $mpStatus = $paymentData['status'] ?? 'unknown';
                    $dateOfExpiration = isset($paymentData['date_of_expiration'])
                        ? Carbon::parse($paymentData['date_of_expiration'])
                        : null;

                    if (in_array($mpStatus, ['approved', 'authorized', 'in_process'], true)) {
                        return true;
                    }

                    if ($mpStatus === 'pending' && $dateOfExpiration && $dateOfExpiration->isFuture()) {
                        return true;
                    }

                    return false;
                } elseif ($response->status() !== 404) {
                    Log::error("MercadoPagoConsultaService: Error HTTP {$response->status()} al consultar pago {$pedido->mercado_pago_id}", [
                        'pedido_id' => $pedido->id,
                    ]);
                    return true; // Ante error de API distinto de 404, por seguridad NO cancelar
                }
            } catch (\Exception $e) {
                Log::error("MercadoPagoConsultaService: Excepción al consultar pago {$pedido->mercado_pago_id}", [
                    'pedido_id' => $pedido->id,
                    'error'     => $e->getMessage(),
                ]);
                return true; // Ante excepción de red, por seguridad NO cancelar
            }
        }

        // 2. Consultar por external_reference (ID del pedido) para detectar cualquier pago activo en MP
        try {
            $searchResponse = Http::timeout(5)
                ->withToken($accessToken)
                ->get('https://api.mercadopago.com/v1/payments/search', [
                    'external_reference' => (string) $pedido->id,
                ]);

            if ($searchResponse->successful()) {
                $results = $searchResponse->json('results') ?? [];
                foreach ($results as $payment) {
                    $pStatus = $payment['status'] ?? '';
                    $dateOfExpiration = isset($payment['date_of_expiration'])
                        ? Carbon::parse($payment['date_of_expiration'])
                        : null;

                    if (in_array($pStatus, ['approved', 'authorized', 'in_process'], true)) {
                        return true;
                    }

                    if ($pStatus === 'pending' && $dateOfExpiration && $dateOfExpiration->isFuture()) {
                        return true;
                    }
                }
            } elseif ($searchResponse->status() !== 404) {
                Log::error("MercadoPagoConsultaService: Error HTTP {$searchResponse->status()} al buscar pagos por external_reference", [
                    'pedido_id' => $pedido->id,
                ]);
                return true; // Ante error de API distinto de 404, por seguridad NO cancelar
            }
        } catch (\Exception $e) {
            Log::error("MercadoPagoConsultaService: Excepción al buscar pagos por external_reference", [
                'pedido_id' => $pedido->id,
                'error'     => $e->getMessage(),
            ]);
            return true; // Ante excepción de red, por seguridad NO cancelar
        }

        return false;
    }
}
