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
        Schema::table('producto_subcategoria', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['subcategoria_id']);

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->foreign('subcategoria_id')->references('id')->on('subcategorias')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_subcategoria', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['subcategoria_id']);

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');
            $table->foreign('subcategoria_id')->references('id')->on('subcategorias')->onDelete('set null');
        });
    }
};
