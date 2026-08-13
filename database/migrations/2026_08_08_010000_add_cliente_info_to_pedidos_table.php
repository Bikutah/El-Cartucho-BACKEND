<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $columns = [
            'cliente_nombre'       => 'VARCHAR(255) NULL',
            'cliente_apellido'     => 'VARCHAR(255) NULL',
            'cliente_email'        => 'VARCHAR(255) NULL',
            'cliente_domicilio'    => 'VARCHAR(500) NULL',
            'cliente_ciudad'       => 'VARCHAR(255) NULL',
            'cliente_codigo_postal'=> 'VARCHAR(20) NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('pedidos', $column)) {
                DB::statement("ALTER TABLE pedidos ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function down(): void
    {
        $columns = ['cliente_nombre', 'cliente_apellido', 'cliente_email', 'cliente_domicilio', 'cliente_ciudad', 'cliente_codigo_postal'];

        foreach ($columns as $col) {
            if (Schema::hasColumn('pedidos', $col)) {
                DB::statement("ALTER TABLE pedidos DROP COLUMN {$col}");
            }
        }
    }
};
