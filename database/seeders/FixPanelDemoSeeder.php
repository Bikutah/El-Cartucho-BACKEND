<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;

class FixPanelDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $cat1 = Categoria::firstOrCreate(['nombre' => 'Consolas Retro'], ['descripcion' => 'Consolas clásicas']);
        $cat2 = Categoria::firstOrCreate(['nombre' => 'Cartuchos'], ['descripcion' => 'Juegos de cartucho']);
        $cat3 = Categoria::firstOrCreate(['nombre' => 'CDs de Juegos'], ['descripcion' => 'Juegos en disco']);
        $cat4 = Categoria::firstOrCreate(['nombre' => 'Accesorios Retro'], ['descripcion' => 'Mandos y cables']);

        Subcategoria::firstOrCreate(['nombre' => 'NES / Famicom', 'categoria_id' => $cat1->id]);
        Subcategoria::firstOrCreate(['nombre' => 'Game Boy', 'categoria_id' => $cat1->id]);

        $p2 = Producto::firstOrCreate(
            ['nombre' => 'Super Nintendo Set Demo'],
            ['descripcion' => 'Pack retro', 'precioUnitario' => 150000, 'stock' => 5, 'categoria_id' => $cat1->id]
        );
        $p2->categorias()->sync([$cat1->id, $cat2->id]);

        $p4 = Producto::firstOrCreate(
            ['nombre' => 'Mega Pack Retro Deluxe'],
            ['descripcion' => 'Mega lote con 4 categorías', 'precioUnitario' => 500000, 'stock' => 2, 'categoria_id' => $cat1->id]
        );
        $p4->categorias()->sync([$cat1->id, $cat2->id, $cat3->id, $cat4->id]);

        $p0 = Producto::firstOrCreate(
            ['nombre' => 'Producto Genérico Solitario'],
            ['descripcion' => 'Sin categoría asociada', 'precioUnitario' => 10000, 'stock' => 10, 'categoria_id' => null]
        );
        $p0->categorias()->detach();
    }
}
