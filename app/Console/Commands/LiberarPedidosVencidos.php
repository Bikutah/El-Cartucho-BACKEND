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

use App\Services\MercadoPagoConsultaService;

class LiberarPedidosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:liberar-vencidos {--limit=25} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pendientes que han vencido y devuelve su stock a los productos.';

    /**
     * Execute the console command.
     */
    public function handle(MercadoPagoConsultaService $mpConsultaService)
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Iniciando proceso de liberación de pedidos vencidos...' . ($isDryRun ? ' [DRY-RUN]' : ''));
        Log::info('LiberarPedidosVencidos: Iniciando proceso' . ($isDryRun ? ' (DRY-RUN)' : '') . '...');

        $now = now();
        $legacyHours = config('mercadopago.legacy_expiration_hours', 96);
        $legacyThreshold = $now->copy()->subHours($legacyHours);

        // Buscar pedidos con estado_pago 'pendiente'
        $query = Pedido::where('estado_pago', 'pendiente')
            ->where(function ($q) use ($now, $legacyThreshold) {
                $q->whereNotNull('expira_at')
                  ->where('expira_at', '<=', $now)
                  ->orWhere(function ($subQuery) use ($legacyThreshold) {
                      // Fallback para pedidos legados (legacy_expiration_hours)
                      $subQuery->whereNull('expira_at')
                               ->where('created_at', '<=', $legacyThreshold);
                  });
            });

        if ($limit > 0) {
            $query->take($limit);
        }

        $pedidos = $query->get();

        $this->info("Se encontraron {$pedidos->count()} pedidos con expiración teórica superada.");

        $canceladosCount = 0;

        foreach ($pedidos as $pedido) {
            $expirationDateUsed = $pedido->expira_at 
                ? $pedido->expira_at->toIso8601String() 
                : $pedido->created_at->addHours($legacyHours)->toIso8601String() . " (Legacy Fallback)";

            // Usar el servicio compartido para verificar si el pedido tiene un pago activo en MP
            if ($mpConsultaService->tienePagoVivo($pedido)) {
                $this->line("Pedido #{$pedido->id} tiene un pago activo en MercadoPago. Omitiendo.");
                continue;
            }

            $detalles = DetallePedido::where('pedido_id', $pedido->id)->get();
            $detallesStockRepuesto = [];

            if ($isDryRun) {
                foreach ($detalles as $detalle) {
                    $producto = Producto::find($detalle->producto_id);
                    if ($producto) {
                        $detallesStockRepuesto[] = "Producto ID: {$producto->id} ({$producto->nombre}) - Cantidad: {$detalle->cantidad}";
                    }
                }

                $canceladosCount++;

                $logMsg = sprintf(
                    "[DRY-RUN] Pedido #%d sería cancelado por vencimiento. Expiración usada: %s. Stock a devolver: [%s]",
                    $pedido->id,
                    $expirationDateUsed,
                    implode(', ', $detallesStockRepuesto)
                );
                Log::info("LiberarPedidosVencidos: " . $logMsg);
                $this->info("[DRY-RUN] Pedido #{$pedido->id} sería cancelado. Stock a devolver: [" . implode(', ', $detallesStockRepuesto) . "]");
                continue;
            }

            // Proceder a cancelar el pedido y reponer el stock transaccionalmente
            DB::beginTransaction();
            try {
                // Bloquear el pedido para actualización
                $pedidoParaCancelar = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

                // Verificar de nuevo el estado por si cambió concurrentemente
                if ($pedidoParaCancelar && $pedidoParaCancelar->estado_pago === 'pendiente') {
                    $pedidoParaCancelar->cambiarEstadoPago('expirado', 'comando');

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

        if ($isDryRun) {
            $this->info("Proceso DRY-RUN completado. Se habrían liberado {$canceladosCount} pedidos vencidos.");
        } else {
            $this->info("Proceso completado. Se liberaron y cancelaron {$canceladosCount} pedidos vencidos.");
            Log::info("LiberarPedidosVencidos: Proceso completado. Pedidos cancelados: {$canceladosCount}.");
        }
    }
}
