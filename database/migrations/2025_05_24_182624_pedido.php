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
        Schema::create('pedidos', function (Blueprint $table) {
        $table->id();
        $table->string('firebase_uid'); // usuario autenticado desde Firebase
        $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
        $table->string('mercado_pago_id')->nullable(); // ID de pago en MP
        $table->decimal('total', 10, 2);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
