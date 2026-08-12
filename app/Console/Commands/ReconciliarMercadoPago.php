<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconciliarMercadoPago extends Command
{
    /**
     * El nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'pedidos:reconciliar-mercadopago {--force : Ejecutar los cambios en la base de datos (por defecto es dry-run)}';

    /**
     * La descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Consulta la API de MercadoPago por external_reference y reconcilia pedidos pendientes cuyo pago fue aprobado.';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $isForce = (bool) $this->option('force');
        $isDryRun = !$isForce;

        if ($isDryRun) {
            $this->info('--- MODO DRY-RUN (SIMULACIÓN POR DEFECTO) ---');
            $this->info('No se modificará la base de datos. Usá el flag --force para aplicar los cambios.');
        } else {
            $this->warn('--- MODO REAL (--force) ---');
            $this->warn('Se aplicarán los cambios en la base de datos.');
        }

        $accessToken = config('services.mercadopago.access_token');
        if (!$accessToken) {
            $this->error('Error: config("services.mercadopago.access_token") no está configurado.');
            return 1;
        }

        $pedidosPendientes = Pedido::where('estado', 'pendiente')->orderBy('id')->get();
        $totalPendientes = $pedidosPendientes->count();

        $this->info("Se encontraron {$totalPendientes} pedidos en estado 'pendiente'.");

        $reconciliados = 0;
        $rechazados = 0;
        $sinPago = 0;
        $errores = 0;

        foreach ($pedidosPendientes as $pedido) {
            try {
                $response = Http::timeout(5)
                    ->withToken($accessToken)
                    ->get('https://api.mercadopago.com/v1/payments/search', [
                        'external_reference' => (string) $pedido->id,
                    ]);

                if (!$response->successful()) {
                    $this->error("Error HTTP {$response->status()} al consultar MercadoPago para el pedido #{$pedido->id}.");
                    Log::error("ReconciliarMercadoPago: Error API MercadoPago", [
                        'pedido_id' => $pedido->id,
                        'status' => $response->status(),
                    ]);
                    $errores++;
                    continue;
                }

                $results = $response->json('results') ?? [];

                $approvedPayment = null;
                $rejectedPayments = [];

                foreach ($results as $payment) {
                    $pStatus = $payment['status'] ?? '';
                    if ($pStatus === 'approved') {
                        $approvedPayment = $payment;
                        break;
                    } elseif (in_array($pStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'])) {
                        $rejectedPayments[] = $payment;
                    }
                }

                if ($approvedPayment) {
                    $paymentId = (string) $approvedPayment['id'];

                    if ($isDryRun) {
                        $this->line("<info>[DRY-RUN]</info> Pedido #{$pedido->id} pasaría de 'pendiente' a 'pagado' (Payment ID: {$paymentId}).");
                        Log::info("ReconciliarMercadoPago (DRY-RUN): Pedido #{$pedido->id} pasaría a pagado", [
                            'pedido_id' => $pedido->id,
                            'payment_id' => $paymentId,
                            'estado_anterior' => 'pendiente',
                            'estado_nuevo' => 'pagado',
                        ]);
                    } else {
                        DB::beginTransaction();
                        try {
                            $pedidoLock = Pedido::where('id', $pedido->id)->lockForUpdate()->first();
                            if ($pedidoLock && $pedidoLock->estado === 'pendiente') {
                                $estadoAnterior = $pedidoLock->estado;
                                $pedidoLock->estado = 'pagado';
                                $pedidoLock->mercado_pago_id = $paymentId;
                                $pedidoLock->save();

                                Log::info("Reconciliación MercadoPago: Pedido {$pedidoLock->id} actualizado de {$estadoAnterior} a pagado", [
                                    'pedido_id' => $pedidoLock->id,
                                    'payment_id' => $paymentId,
                                    'estado_anterior' => $estadoAnterior,
                                    'estado_nuevo' => 'pagado',
                                ]);

                                $this->info("[RECONCILIADO] Pedido #{$pedidoLock->id} actualizado a 'pagado' (Payment ID: {$paymentId}).");
                            }
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->error("Error al guardar pedido #{$pedido->id}: {$e->getMessage()}");
                            Log::error("ReconciliarMercadoPago: Error al guardar pedido #{$pedido->id}", [
                                'pedido_id' => $pedido->id,
                                'error' => $e->getMessage(),
                            ]);
                            $errores++;
                            continue;
                        }
                    }
                    $reconciliados++;
                } elseif (!empty($rejectedPayments)) {
                    $firstRejected = $rejectedPayments[0];
                    $rejPaymentId = (string) ($firstRejected['id'] ?? '');
                    $rejStatus = $firstRejected['status'] ?? 'desconocido';

                    $this->warn("[RECHAZADO DETECTADO] Pedido #{$pedido->id} tiene un pago en estado '{$rejStatus}' (Payment ID: {$rejPaymentId}). No se modifica el pedido.");
                    Log::warning("Reconciliación MercadoPago: Pago rechazado detectado para Pedido {$pedido->id}", [
                        'pedido_id' => $pedido->id,
                        'payment_id' => $rejPaymentId,
                        'status' => $rejStatus,
                    ]);
                    $rechazados++;
                } else {
                    $this->line("[SIN PAGO] Pedido #{$pedido->id} no tiene pagos registrados en MercadoPago.");
                    $sinPago++;
                }
            } catch (\Exception $e) {
                $this->error("Excepción al procesar pedido #{$pedido->id}: {$e->getMessage()}");
                Log::error("ReconciliarMercadoPago: Excepción al consultar pedido #{$pedido->id}", [
                    'pedido_id' => $pedido->id,
                    'error' => $e->getMessage(),
                ]);
                $errores++;
            }
        }

        $this->newLine();
        $this->info("--- RESUMEN ---");
        $this->info("Total procesados: {$totalPendientes}");
        $this->info("Reconciliados (approved): {$reconciliados}" . ($isDryRun ? " (DRY-RUN)" : ""));
        $this->info("Pagos rechazados (sin modificar): {$rechazados}");
        $this->info("Sin pago: {$sinPago}");
        if ($errores > 0) {
            $this->warn("Errores API/red: {$errores}");
        }

        return 0;
    }
}
