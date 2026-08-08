<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disable wrapping in a transaction for PostgreSQL compatibility.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToAdd = [
            'firebase_uid' => 'VARCHAR(255) NULL',
            'apellido'     => 'VARCHAR(255) NULL',
            'domicilio'    => 'VARCHAR(255) NULL',
            'ciudad'       => 'VARCHAR(255) NULL',
            'codigo_postal'=> 'VARCHAR(255) NULL',
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (!Schema::hasColumn('users', $column)) {
                DB::statement("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }

        // Add unique index for firebase_uid if it doesn't already exist
        if (Schema::hasColumn('users', 'firebase_uid')) {
            try {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_firebase_uid_unique ON users (firebase_uid)");
            } catch (\Exception $e) {
                // Index may already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['firebase_uid', 'apellido', 'domicilio', 'ciudad', 'codigo_postal'];

        foreach ($columns as $col) {
            if (Schema::hasColumn('users', $col)) {
                DB::statement("ALTER TABLE users DROP COLUMN {$col}");
            }
        }
    }
};
