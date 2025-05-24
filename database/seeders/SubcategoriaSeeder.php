<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Subcategoria;

class SubcategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $subcategorias = [
            'Consolas Retro' => [
                'Nintendo NES', 'Super Nintendo', 'Sega Genesis', 'Atari 2600'
            ],
            'Cartuchos' => [
                'NES', 'SNES', 'Nintendo 64', 'Sega Genesis'
            ],
            'CDs de Juegos' => [
                'PlayStation 1', 'Sega CD', 'Dreamcast', 'Neo Geo CD'
            ],
            'Accesorios Retro' => [
                'Memory Cards', 'Multitaps', 'Adaptadores de corriente'
            ],
            'Posters Retro' => [
                'Nintendo Power', 'Revistas Club Nintendo', 'Promocionales japoneses'
            ],
            'Joystick Retro' => [
                'Joystick Atari', 'Control SNES', 'Control N64'
            ],
            'Revistas de Videojuegos' => [
                'Club Nintendo', 'Hobby Consolas', 'GamePro'
            ],
            'Arcades' => [
                'PCB originales', 'Controles arcade', 'Gabinetes completos'
            ],
            'Pinballs' => [
                'Bolas de repuesto', 'Tableros', 'Luces y electrónica'
            ],
            'Manuales de Juegos' => [
                'Manual Zelda NES', 'Instrucciones de juegos SNES'
            ],
            'Cajas Originales' => [
                'Cajas NES', 'Cajas SNES', 'Cajas PlayStation'
            ],
            'Merchandising Retro' => [
                'Posters promocionales', 'Stickers oficiales'
            ],
            'Figuras de Videojuegos' => [
                'Mario', 'Link', 'Sonic', 'Megaman'
            ],
            'Peluches de Videojuegos' => [
                'Peluches Kirby', 'Peluches Pokémon'
            ],
            'Vinilos y Música Gamer' => [
                'OST NES', 'Remixes retro'
            ],
            'Ropa Gamer Retro' => [
                'Remeras', 'Gorras', 'Buzos'
            ],
            'Postales Retro' => [
                'Ilustraciones de revistas', 'Postales Club Nintendo'
            ],
            'Hardware Retro' => [
                'Cables RCA', 'Transformadores', 'Lector de cartuchos'
            ],
            'Publicidad Retro' => [
                'Anuncios en revistas', 'Folletería oficial'
            ],
            'Juegos de PC Antiguos' => [
                'MS-DOS', 'Windows 95', 'Windows 98'
            ],
            'Computadoras Retro' => [
                'Commodore 64', 'Amiga 500', 'MSX'
            ],
            'Televisores CRT' => [
                'Sony Trinitron', 'Philips', 'Panasonic'
            ],
            'Repuestos de Consolas' => [
                'Botones', 'Carcasas', 'Pantallas portátiles'
            ],
            'Guías Estratégicas' => [
                'Guía Final Fantasy', 'Mapas Donkey Kong Country'
            ],
            'Llaveros Retro' => [
                'Mario', 'Pac-Man', 'Tetris'
            ],
            'Mochilas Gamer Retro' => [
                'Game Boy', 'Sonic', 'Street Fighter'
            ],
            'Ediciones de Colección' => [
                'Collector’s Edition', 'Steelbooks', 'Figuras'
            ],
            'Tarjetas Coleccionables' => [
                'Stickers Panini', 'Cartas Pokémon'
            ],
            'Consolas Portátiles Retro' => [
                'Game Boy', 'Game Gear', 'Neo Geo Pocket'
            ],
            'Cultura Pop Gamer' => [
                'Eventos', 'Documentales', 'Arte conceptual'
            ],
        ];

        foreach ($subcategorias as $categoriaNombre => $subs) {
            $categoria = Categoria::where('nombre', $categoriaNombre)->first();

            if ($categoria) {
                foreach ($subs as $sub) {
                    Subcategoria::firstOrCreate([
                        'nombre' => $sub,
                        'categoria_id' => $categoria->id,
                    ]);
                }
            }
        }
    }
}
