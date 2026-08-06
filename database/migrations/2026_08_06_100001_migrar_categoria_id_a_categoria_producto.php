<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Cargar IDs de categorías existentes para validación eficiente
            $categoriaIds = DB::table('categorias')->pluck('id')->flip()->toArray();

            $huerfanosCount = 0;
            $now = now();

            DB::table('productos')
                ->whereNotNull('categoria_id')
                ->orderBy('id')
                ->chunkById(100, function ($productos) use ($categoriaIds, &$huerfanosCount, $now) {
                    $insertData = [];

                    foreach ($productos as $producto) {
                        if (!isset($categoriaIds[$producto->categoria_id])) {
                            $huerfanosCount++;
                            Log::warning("Producto ID {$producto->id} referencia a categoria_id inexistente: {$producto->categoria_id}");
                            continue;
                        }

                        $insertData[] = [
                            'producto_id' => $producto->id,
                            'categoria_id' => $producto->categoria_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (!empty($insertData)) {
                        DB::table('categoria_producto')->insertOrIgnore($insertData);
                    }
                });

            if ($huerfanosCount > 0) {
                Log::warning("Migración de datos: Se encontraron {$huerfanosCount} vínculos con categoria_id inexistente.");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            DB::table('categoria_producto')->truncate();
        });
    }
};
