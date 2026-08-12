<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->decimal('costo_envio', 10, 2)->default(0)->after('codigo_postal');
            $table->foreignId('zona_envio_id')->nullable()->after('costo_envio')->constrained('zonas_envio')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['zona_envio_id']);
            $table->dropColumn(['costo_envio', 'zona_envio_id']);
        });
    }
};
