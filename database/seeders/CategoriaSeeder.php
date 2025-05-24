<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Consolas Retro', 'descripcion' => 'Consolas clásicas como NES, SNES, Sega Genesis, etc.'],
            ['nombre' => 'Cartuchos', 'descripcion' => 'Cartuchos de juegos para consolas antiguas.'],
            ['nombre' => 'CDs de Juegos', 'descripcion' => 'Juegos físicos en formato CD de consolas retro.'],
            ['nombre' => 'Accesorios Retro', 'descripcion' => 'Accesorios clásicos como adaptadores, memory cards, etc.'],
            ['nombre' => 'Posters Retro', 'descripcion' => 'Posters originales o reimpresiones de videojuegos retro.'],
            ['nombre' => 'Joystick Retro', 'descripcion' => 'Joysticks y controles clásicos.'],
            ['nombre' => 'Revistas de Videojuegos', 'descripcion' => 'Revistas de la época dorada del gaming.'],
            ['nombre' => 'Arcades', 'descripcion' => 'Máquinas arcade y partes.'],
            ['nombre' => 'Pinballs', 'descripcion' => 'Máquinas y partes de pinball clásicas.'],
            ['nombre' => 'Manuales de Juegos', 'descripcion' => 'Instrucciones y manuales originales.'],
            ['nombre' => 'Cajas Originales', 'descripcion' => 'Cajas de cartón o plástico de juegos y consolas.'],
            ['nombre' => 'Merchandising Retro', 'descripcion' => 'Productos promocionales antiguos.'],
            ['nombre' => 'Figuras de Videojuegos', 'descripcion' => 'Figuras coleccionables de personajes retro.'],
            ['nombre' => 'Peluches de Videojuegos', 'descripcion' => 'Peluches inspirados en personajes retro.'],
            ['nombre' => 'Vinilos y Música Gamer', 'descripcion' => 'Música original o remix de videojuegos.'],
            ['nombre' => 'Ropa Gamer Retro', 'descripcion' => 'Remeras o gorras con diseños retro (categoría general).'],
            ['nombre' => 'Postales Retro', 'descripcion' => 'Postales o ilustraciones antiguas de videojuegos.'],
            ['nombre' => 'Hardware Retro', 'descripcion' => 'Componentes viejos: cables, fuentes, lectores.'],
            ['nombre' => 'Publicidad Retro', 'descripcion' => 'Anuncios y afiches antiguos de consolas/juegos.'],
            ['nombre' => 'Juegos de PC Antiguos', 'descripcion' => 'Juegos de DOS o primeras versiones de Windows.'],
            ['nombre' => 'Computadoras Retro', 'descripcion' => 'Modelos como Amiga, Commodore, MSX, etc.'],
            ['nombre' => 'Televisores CRT', 'descripcion' => 'Pantallas antiguas usadas para jugar.'],
            ['nombre' => 'Repuestos de Consolas', 'descripcion' => 'Piezas para reparación de consolas retro.'],
            ['nombre' => 'Guías Estratégicas', 'descripcion' => 'Libros y guías con trucos y mapas.'],
            ['nombre' => 'Llaveros Retro', 'descripcion' => 'Llaveros temáticos de videojuegos clásicos.'],
            ['nombre' => 'Mochilas Gamer Retro', 'descripcion' => 'Mochilas con diseños de consolas/juegos retro.'],
            ['nombre' => 'Ediciones de Colección', 'descripcion' => 'Packs especiales con contenido extra.'],
            ['nombre' => 'Tarjetas Coleccionables', 'descripcion' => 'Cartas y stickers de videojuegos.'],
            ['nombre' => 'Consolas Portátiles Retro', 'descripcion' => 'Game Boy, PSP, Neo Geo Pocket, etc.'],
            ['nombre' => 'Cultura Pop Gamer', 'descripcion' => 'Todo lo relacionado al entorno cultural gamer antiguo.'],
        ];

        foreach ($categorias as $cat) {
            Categoria::firstOrCreate(['nombre' => $cat['nombre']], [
                'descripcion' => $cat['descripcion']
            ]);
        }
    }
}
