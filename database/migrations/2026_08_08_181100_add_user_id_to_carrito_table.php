<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega user_id a carrito con FK a users y hace el backfill desde firebase_uid.
     * La columna firebase_uid se conserva como respaldo.
     * El índice único pasa de [firebase_uid, producto_id] a [user_id, producto_id].
     */
    public function up(): void
    {
        Schema::table('carrito', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('firebase_uid');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Backfill compatible con SQLite y PostgreSQL
        DB::statement("
            UPDATE carrito
            SET user_id = (
                SELECT id FROM users WHERE users.firebase_uid = carrito.firebase_uid
            )
            WHERE firebase_uid IS NOT NULL
        ");

        // Reemplazar el índice único [firebase_uid, producto_id] por [user_id, producto_id]
        Schema::table('carrito', function (Blueprint $table) {
            $table->dropUnique(['firebase_uid', 'producto_id']);
        });

        DB::statement('
            CREATE UNIQUE INDEX carrito_user_id_producto_id_unique
            ON carrito (user_id, producto_id)
            WHERE user_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS carrito_user_id_producto_id_unique');

        Schema::table('carrito', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->unique(['firebase_uid', 'producto_id']);
        });
    }
};
