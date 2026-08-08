<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos snapshot de envío a la tabla pedidos.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('email')->nullable()->after('user_id');
            $table->string('domicilio')->nullable()->after('email');
            $table->string('ciudad')->nullable()->after('domicilio');
            $table->string('codigo_postal')->nullable()->after('ciudad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['email', 'domicilio', 'ciudad', 'codigo_postal']);
        });
    }
};
