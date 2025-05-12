<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Str;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productosPorCategoria = [
            'Consolas Retro' => [
                ['nombre' => 'NES Original', 'descripcion' => 'Consola Nintendo Entertainment System funcional.'],
                ['nombre' => 'Sega Genesis', 'descripcion' => 'Modelo clásico con puerto de cartuchos.']
            ],
            'Cartuchos' => [
                ['nombre' => 'Cartucho Super Mario Bros', 'descripcion' => 'Cartucho original para NES.'],
                ['nombre' => 'The Legend of Zelda: Ocarina of Time', 'descripcion' => 'Cartucho para Nintendo 64.']
            ],
            'CDs de Juegos' => [
                ['nombre' => 'Final Fantasy VII', 'descripcion' => 'Juego en CD para PlayStation 1.'],
                ['nombre' => 'Metal Gear Solid', 'descripcion' => 'Versión completa para PS1.']
            ],
            'Accesorios Retro' => [
                ['nombre' => 'Memory Card PS1', 'descripcion' => 'Tarjeta de memoria original de Sony.'],
                ['nombre' => 'Adaptador Multitap', 'descripcion' => 'Permite conectar más controles.']
            ],
            'Posters Retro' => [
                ['nombre' => 'Poster Donkey Kong', 'descripcion' => 'Poster original de arcade de los 80s.'],
                ['nombre' => 'Poster Sonic', 'descripcion' => 'Ilustración vintage de Sonic.']
            ],
            'Joystick Retro' => [
                ['nombre' => 'Joystick Atari 2600', 'descripcion' => 'Clásico joystick negro con botón rojo.'],
                ['nombre' => 'Control SNES', 'descripcion' => 'Control original gris de Super Nintendo.']
            ],
            // ...podés seguir agregando más si querés cubrir todas las categorías...
        ];

        $imagenes = [
            ['url' => 'https://via.placeholder.com/100x100.png?text=Retro1', 'public_id' => 'img1'],
            ['url' => 'https://via.placeholder.com/100x100.png?text=Retro2', 'public_id' => 'img2'],
            ['url' => 'https://via.placeholder.com/100x100.png?text=Retro3', 'public_id' => 'img3'],
        ];

        $categorias = Categoria::all();

        foreach ($categorias as $categoria) {
            $productos = $productosPorCategoria[$categoria->nombre] ?? [
                ['nombre' => 'Producto genérico de ' . $categoria->nombre, 'descripcion' => 'Descripción de ejemplo.']
            ];

            foreach ($productos as $producto) {
                $imagen = $imagenes[array_rand($imagenes)];

                Producto::create([
                    'nombre' => $producto['nombre'],
                    'descripcion' => $producto['descripcion'],
                    'precioUnitario' => mt_rand(500, 20000) / 100,
                    'stock' => mt_rand(5, 50),
                    'image_url' => $imagen['url'],
                    'image_public_id' => $imagen['public_id'] . '_' . Str::random(5),
                    'categoria_id' => $categoria->id,
                ]);
            }
        }
    }
}
