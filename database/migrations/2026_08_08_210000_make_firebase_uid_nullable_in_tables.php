<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hace la columna firebase_uid nullable en carrito, wishlist y pedidos.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS carrito_user_id_producto_id_unique');
            DB::statement('DROP INDEX IF EXISTS wishlist_user_id_producto_id_unique');

            Schema::table('carrito', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable()->change();
            });
            Schema::table('wishlist', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable()->change();
            });
            Schema::table('pedidos', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable()->change();
            });

            DB::statement('CREATE UNIQUE INDEX carrito_user_id_producto_id_unique ON carrito (user_id, producto_id) WHERE user_id IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX wishlist_user_id_producto_id_unique ON wishlist (user_id, producto_id) WHERE user_id IS NOT NULL');
        } else {
            DB::statement('ALTER TABLE carrito ALTER COLUMN firebase_uid DROP NOT NULL');
            DB::statement('ALTER TABLE wishlist ALTER COLUMN firebase_uid DROP NOT NULL');
            DB::statement('ALTER TABLE pedidos ALTER COLUMN firebase_uid DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('carrito', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable(false)->change();
            });
            Schema::table('wishlist', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable(false)->change();
            });
            Schema::table('pedidos', function (Blueprint $table) {
                $table->string('firebase_uid')->nullable(false)->change();
            });
        } else {
            DB::statement('ALTER TABLE carrito ALTER COLUMN firebase_uid SET NOT NULL');
            DB::statement('ALTER TABLE wishlist ALTER COLUMN firebase_uid SET NOT NULL');
            DB::statement('ALTER TABLE pedidos ALTER COLUMN firebase_uid SET NOT NULL');
        }
    }
};
