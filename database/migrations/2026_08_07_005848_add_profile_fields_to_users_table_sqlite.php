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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'apellido')) {
                $table->string('apellido')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'domicilio')) {
                $table->string('domicilio')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'ciudad')) {
                $table->string('ciudad')->nullable()->after('domicilio');
            }
            if (!Schema::hasColumn('users', 'codigo_postal')) {
                $table->string('codigo_postal')->nullable()->after('ciudad');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['firebase_uid', 'apellido', 'domicilio', 'ciudad', 'codigo_postal'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
