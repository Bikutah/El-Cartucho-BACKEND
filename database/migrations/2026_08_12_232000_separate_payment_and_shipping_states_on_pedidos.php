<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('estado_pago')->default('pendiente')->index()->after('estado');
            $table->string('estado_envio')->nullable()->index()->after('estado_pago');
            $table->string('transportista')->nullable()->after('estado_envio');
            $table->string('tracking_numero')->nullable()->after('transportista');
            $table->timestamp('enviado_at')->nullable()->after('tracking_numero');
            $table->timestamp('entregado_at')->nullable()->after('enviado_at');
        });

        Schema::create('pedido_historial_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('tipo'); // 'pago' | 'envio'
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origen'); // 'webhook' | 'panel' | 'comando' | 'sistema'
            $table->text('observacion')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pedido_id', 'created_at']);
        });

        // Migración de datos existentes (estado_envio queda null por defecto)
        $pedidos = DB::table('pedidos')->get();

        foreach ($pedidos as $pedido) {
            $estadoPago = 'pendiente';

            if ($pedido->estado === 'pagado') {
                $estadoPago = 'pagado';
            } elseif ($pedido->estado === 'cancelado') {
                $estadoPago = 'rechazado';
            } else {
                $estadoPago = 'pendiente';
            }

            DB::table('pedidos')
                ->where('id', $pedido->id)
                ->update([
                    'estado_pago'  => $estadoPago,
                    'estado_envio' => null,
                ]);

            // Historial inicial de pago
            DB::table('pedido_historial_estados')->insert([
                'pedido_id'       => $pedido->id,
                'tipo'            => 'pago',
                'estado_anterior' => null,
                'estado_nuevo'    => $estadoPago,
                'user_id'         => null,
                'origen'          => 'sistema',
                'observacion'     => 'Migración inicial de estado de pago',
                'created_at'      => $pedido->created_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $pedidos = DB::table('pedidos')->get();

        foreach ($pedidos as $pedido) {
            $estadoViejo = 'pendiente';
            if ($pedido->estado_pago === 'pagado') {
                $estadoViejo = 'pagado';
            } elseif (in_array($pedido->estado_pago, ['rechazado', 'expirado', 'reembolsado', 'cancelado'])) {
                $estadoViejo = 'cancelado';
            }

            DB::table('pedidos')
                ->where('id', $pedido->id)
                ->update(['estado' => $estadoViejo]);
        }

        Schema::dropIfExists('pedido_historial_estados');

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['estado_pago']);
            $table->dropIndex(['estado_envio']);
            $table->dropColumn([
                'estado_pago',
                'estado_envio',
                'transportista',
                'tracking_numero',
                'enviado_at',
                'entregado_at',
            ]);
        });
    }
};
