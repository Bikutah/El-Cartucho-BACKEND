<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LiberarPedidosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:liberar-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pendientes que han vencido y devuelve su stock a los productos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de liberación de pedidos vencidos...');
        Log::info('LiberarPedidosVencidos: Iniciando proceso...');

        $now = now();
        $legacyHours = config('mercadopago.legacy_expiration_hours', 96);
        $legacyThreshold = $now->copy()->subHours($legacyHours);

        // Buscar pedidos con estado 'pendiente'
        $pedidos = Pedido::where('estado', 'pendiente')
            ->where(function ($query) use ($now, $legacyThreshold) {
                $query->whereNotNull('expira_at')
                      ->where('expira_at', '<=', $now)
                      ->orWhere(function ($subQuery) use ($legacyThreshold) {
                          // Fallback para pedidos legados (legacy_expiration_hours)
                          $subQuery->whereNull('expira_at')
                                   ->where('created_at', '<=', $legacyThreshold);
                      });
            })
            ->get();

        $this->info("Se encontraron {$pedidos->count()} pedidos con expiración teórica superada.");

        $canceladosCount = 0;

        foreach ($pedidos as $pedido) {
            $expirationDateUsed = $pedido->expira_at 
                ? $pedido->expira_at->toIso8601String() 
                : $pedido->created_at->addHours($legacyHours)->toIso8601String() . " (Legacy Fallback)";

            // Si tiene mercado_pago_id, verificar contra la API de MercadoPago
            if ($pedido->mercado_pago_id) {
                try {
                    $response = Http::withToken(config('mercadopago.access_token'))
                        ->get("https://api.mercadopago.com/v1/payments/{$pedido->mercado_pago_id}");

                    if ($response->successful()) {
                        $paymentData = $response->json();
                        $mpStatus = $paymentData['status'] ?? 'unknown';
                        $dateOfExpiration = isset($paymentData['date_of_expiration']) 
                            ? Carbon::parse($paymentData['date_of_expiration']) 
                            : null;

                        // Si el pago sigue pendiente en MP y su fecha de vencimiento NO ha pasado, NO cancelamos
                        if ($mpStatus === 'pending' && $dateOfExpiration && $dateOfExpiration->isFuture()) {
                            $this->line("Pedido #{$pedido->id} sigue pendiente y no venció en MercadoPago (Vence: {$dateOfExpiration->toIso8601String()}). Omitiendo.");
                            continue;
                        }
                    } elseif ($response->status() !== 404) {
                        // Si falla la API con un error distinto a 404 (ej. 500 o timeout), omitimos el pedido por seguridad
                        $this->warn("Error al consultar pago {$pedido->mercado_pago_id} en MercadoPago (Status: {$response->status()}). Omitiendo pedido #{$pedido->id}.");
                        Log::error("LiberarPedidosVencidos: Error de red/API al consultar pago {$pedido->mercado_pago_id}", [
                            'pedido_id' => $pedido->id,
                            'status_code' => $response->status()
                        ]);
                        continue;
                    }
                } catch (\Exception $e) {
                    $this->error("Excepción al consultar pago {$pedido->mercado_pago_id}: {$e->getMessage()}. Omitiendo pedido #{$pedido->id}.");
                    Log::error("LiberarPedidosVencidos: Excepción al consultar pago {$pedido->mercado_pago_id}", [
                        'pedido_id' => $pedido->id,
                        'exception' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            // Proceder a cancelar el pedido y reponer el stock transaccionalmente
            DB::beginTransaction();
            try {
                // Bloquear el pedido para actualización
                $pedidoParaCancelar = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

                // Verificar de nuevo el estado por si cambió concurrentemente
                if ($pedidoParaCancelar && $pedidoParaCancelar->estado === 'pendiente') {
                    $pedidoParaCancelar->estado = 'cancelado';
                    $pedidoParaCancelar->save();

                    $detalles = DetallePedido::where('pedido_id', $pedido->id)->get();
                    $detallesStockRepuesto = [];

                    foreach ($detalles as $detalle) {
                        $producto = Producto::where('id', $detalle->producto_id)->lockForUpdate()->first();
                        if ($producto) {
                            $producto->stock += $detalle->cantidad;
                            $producto->save();

                            $detallesStockRepuesto[] = "Producto ID: {$producto->id} ({$producto->nombre}) - Cantidad: {$detalle->cantidad}";
                        }
                    }

                    DB::commit();
                    $canceladosCount++;

                    // Loguear cada cancelación con la información detallada requerida
                    $logMsg = sprintf(
                        "Pedido #%d cancelado por vencimiento. Expiración usada: %s. Stock repuesto: [%s]",
                        $pedido->id,
                        $expirationDateUsed,
                        implode(', ', $detallesStockRepuesto)
                    );
                    Log::info("LiberarPedidosVencidos: " . $logMsg);
                    $this->info("Pedido #{$pedido->id} cancelado correctamente.");
                } else {
                    DB::rollBack();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error al procesar cancelación del pedido #{$pedido->id}: {$e->getMessage()}");
                Log::error("LiberarPedidosVencidos: Error al cancelar pedido #{$pedido->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Proceso completado. Se liberaron y cancelaron {$canceladosCount} pedidos vencidos.");
        Log::info("LiberarPedidosVencidos: Proceso completado. Pedidos cancelados: {$canceladosCount}.");
    }
}
