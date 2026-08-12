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
        Schema::create('zonas_envio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('cp_desde');
            $table->integer('cp_hasta');
            $table->decimal('costo', 10, 2);
            $table->boolean('activa')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['cp_desde', 'cp_hasta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zonas_envio');
    }
};
