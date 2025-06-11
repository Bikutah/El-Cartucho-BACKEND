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
        Schema::create('producto_subcategoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('set null');
            $table->foreignId('subcategoria_id')->constrained('subcategorias')->onDelete('set null');
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['producto_id', 'subcategoria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_subcategoria');
    }
};
