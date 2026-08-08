<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega user_id a la tabla pedidos con FK a users (nullOnDelete)
     * y realiza el backfill asociando pedidos.firebase_uid con users.firebase_uid.
     * La columna firebase_uid se conserva como respaldo.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('firebase_uid');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Backfill compatible con SQLite y PostgreSQL
        DB::statement("
            UPDATE pedidos
            SET user_id = (
                SELECT id FROM users WHERE users.firebase_uid = pedidos.firebase_uid
            )
            WHERE firebase_uid IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
