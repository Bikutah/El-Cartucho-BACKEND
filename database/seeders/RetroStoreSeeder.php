<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\Imagen;
use Illuminate\Support\Str;

class RetroStoreSeeder extends Seeder
{
    public function run(): void
    {
        $categoriasData = [
            [
                'nombre' => 'Nintendo 64',
                'descripcion' => 'Consola de 64 bits de Nintendo',
                'color' => 'E70012',
                'subcategorias' => ['Consolas', 'Juegos', 'Accesorios', 'Merch'],
                'productos' => [
                    ['Consola Nintendo 64 Gris Original', 'Consola en perfecto estado con cables.', 120000, 2, 'Consolas'],
                    ['Super Mario 64', 'Juego clásico de plataformas en 3D.', 45000, 5, 'Juegos'],
                    ['The Legend of Zelda: Ocarina of Time', 'Aventura épica de Link.', 65000, 3, 'Juegos'],
                    ['Control N64 Original', 'Joystick original con stick firme.', 25000, 4, 'Accesorios'],
                    ['Rumble Pak', 'Accesorio de vibración para el control.', 15000, 6, 'Accesorios']
                ]
            ],
            [
                'nombre' => 'Super Nintendo (SNES)',
                'descripcion' => 'La mítica consola de 16 bits',
                'color' => 'B7B7B7',
                'subcategorias' => ['Consolas', 'Juegos', 'Accesorios', 'Merch'],
                'productos' => [
                    ['Consola Super Nintendo', 'SNES clásico funcionando perfecto.', 110000, 3, 'Consolas'],
                    ['Super Mario World', 'Juego original, cartucho solo.', 35000, 8, 'Juegos'],
                    ['Donkey Kong Country', 'Plataformas de Rareware.', 40000, 4, 'Juegos'],
                    ['Control SNES Original', 'Control clásico de SNES.', 20000, 5, 'Accesorios']
                ]
            ],
            [
                'nombre' => 'Sega Genesis',
                'descripcion' => 'Consola de 16 bits de Sega',
                'color' => '000000',
                'subcategorias' => ['Consolas', 'Juegos', 'Accesorios', 'Merch'],
                'productos' => [
                    ['Consola Sega Genesis Model 2', 'Con transformador original y 1 control.', 90000, 2, 'Consolas'],
                    ['Sonic The Hedgehog 2', 'El clásico juego del erizo azul.', 25000, 6, 'Juegos'],
                    ['Mortal Kombat 3 Ultimate', 'Juego de peleas clásico.', 30000, 3, 'Juegos'],
                    ['Control 6 Botones Sega', 'Joystick ideal para juegos de pelea.', 18000, 4, 'Accesorios']
                ]
            ],
            [
                'nombre' => 'PlayStation 1',
                'descripcion' => 'La primera consola de Sony',
                'color' => '003791',
                'subcategorias' => ['Consolas', 'Juegos', 'Accesorios', 'Merch'],
                'productos' => [
                    ['Consola PS1 Fat', 'PlayStation 1 modelo original.', 85000, 3, 'Consolas'],
                    ['Final Fantasy VII', 'Juego RPG icónico (3 discos).', 75000, 1, 'Juegos'],
                    ['Crash Bandicoot', 'Primer juego del querido marsupial.', 40000, 2, 'Juegos'],
                    ['Memory Card 1MB', 'Tarjeta de memoria de 15 bloques.', 10000, 10, 'Accesorios']
                ]
            ]
        ];

        foreach ($categoriasData as $catData) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $catData['nombre']],
                ['descripcion' => $catData['descripcion']]
            );

            $subcategoriasMap = [];
            foreach ($catData['subcategorias'] as $subNombre) {
                $sub = Subcategoria::firstOrCreate(
                    ['nombre' => $subNombre, 'categoria_id' => $categoria->id]
                );
                $subcategoriasMap[$subNombre] = $sub;
            }

            foreach ($catData['productos'] as $prodData) {
                $nombreProd = $prodData[0];
                $subcatNombre = $prodData[4];
                
                $producto = Producto::firstOrCreate(
                    ['nombre' => $nombreProd],
                    [
                        'descripcion' => $prodData[1],
                        'precioUnitario' => $prodData[2],
                        'stock' => $prodData[3],
                        'categoria_id' => $categoria->id
                    ]
                );
                
                // Relaciones Many to Many
                $producto->categorias()->syncWithoutDetaching([$categoria->id]);
                if (isset($subcategoriasMap[$subcatNombre])) {
                    $producto->subcategorias()->syncWithoutDetaching([$subcategoriasMap[$subcatNombre]->id]);
                }

                // Generar imágenes
                if ($producto->imagenes()->count() === 0) {
                    $slug = Str::slug($nombreProd);
                    $color = $catData['color'];
                    
                    for ($i = 1; $i <= 2; $i++) {
                        $texto = urlencode(str_replace('-', ' ', $slug) . " $i");
                        $imagenUrl = "https://placehold.co/600x600/{$color}/FFF?text={$texto}";
                        $publicId = "retro/{$slug}-{$i}";

                        Imagen::create([
                            'producto_id' => $producto->id,
                            'imagen_url' => $imagenUrl,
                            'imagen_public_id' => $publicId,
                        ]);
                    }
                }
            }
        }
    }
}
