<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\Imagen;
use Illuminate\Support\Str;

class CatalogoDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriasData = [
            [
                'nombre' => 'Consolas',
                'descripcion' => 'Sistemas de videojuegos de última y anterior generación.',
                'color' => '4A90E2', // Blue
                'subcategorias' => ['PlayStation 5', 'Xbox Series X|S', 'Nintendo Switch', 'Steam Deck', 'Consolas reacondicionadas'],
                'productos' => [
                    ['PlayStation 5 Standard Edition', 'Consola PS5 con lectora de discos. Incluye control DualSense.', 990000, 10],
                    ['Xbox Series X 1TB', 'La consola más potente de Microsoft. 1TB de almacenamiento SSD.', 950000, 5],
                    ['Nintendo Switch OLED', 'Consola híbrida con pantalla OLED de 7 pulgadas y colores vibrantes.', 650000, 15],
                    ['Xbox Series S 512GB', 'Consola digital compacta y de próxima generación.', 550000, 0], // Stock 0
                    ['Steam Deck 256GB', 'Consola portátil para juegos de PC de Valve.', 850000, 2], // Stock bajo
                ]
            ],
            [
                'nombre' => 'Videojuegos',
                'descripcion' => 'Juegos físicos y digitales para todas las plataformas.',
                'color' => '50E3C2', // Teal
                'subcategorias' => ['PS5', 'PS4', 'Xbox', 'Nintendo Switch', 'PC', 'Ediciones especiales'],
                'productos' => [
                    ['Marvels Spider-Man 2 PS5', 'Juego físico para PS5. Disfruta de la nueva aventura de Peter y Miles.', 85000, 20],
                    ['The Legend of Zelda: Tears of the Kingdom', 'Juego físico para Nintendo Switch. Explora Hyrule por tierra y cielo.', 75000, 12],
                    ['Halo Infinite Xbox', 'Juego físico para Xbox Series X y Xbox One.', 65000, 8],
                    ['Elden Ring PS4', 'Juego físico para PS4. Juego del año.', 70000, 3], // Stock bajo
                    ['Super Mario Bros Wonder', 'El regreso del clásico Mario en 2D con nuevas sorpresas.', 75000, 25],
                    ['Cyberpunk 2077 Ultimate Edition PS5', 'Incluye el juego base y la expansión Phantom Liberty.', 80000, 5],
                    ['God of War Ragnarok PS5', 'El viaje final de Kratos y Atreus en la mitología nórdica.', 85000, 10],
                    ['Resident Evil 4 Remake PC', 'Clave digital para Steam del clásico survival horror renovado.', 55000, 50],
                    ['FC 24 PS5', 'El nuevo simulador de fútbol de EA Sports.', 90000, 30],
                    ['Mario Kart 8 Deluxe', 'El mejor juego de carreras para jugar con amigos en Switch.', 70000, 18],
                    ['Minecraft Nintendo Switch', 'Edición física del juego de construcción sin límites.', 45000, 15],
                    ['Hogwarts Legacy Xbox Series X', 'Explora el mundo mágico de Harry Potter en este RPG de acción.', 82000, 0], // Stock 0
                ]
            ],
            [
                'nombre' => 'Joysticks y Controles',
                'descripcion' => 'Mandos y volantes para mejorar tu precisión.',
                'color' => 'B8E986', // Light Green
                'subcategorias' => ['DualSense', 'Xbox Wireless', 'Joy-Con', 'Pro Controller', 'Volantes', 'Arcade sticks'],
                'productos' => [
                    ['Control Inalámbrico DualSense Blanco', 'Control oficial para PS5 con respuesta háptica y gatillos adaptativos.', 95000, 40],
                    ['Xbox Wireless Controller Carbon Black', 'Control oficial para Xbox Series X|S y PC.', 90000, 35],
                    ['Nintendo Switch Joy-Con Neon Red/Blue', 'Par de controles originales para Switch.', 105000, 12],
                    ['Nintendo Switch Pro Controller', 'Control tradicional y ergonómico para largas sesiones en Switch.', 110000, 8],
                    ['Logitech G923 Volante', 'Volante de carreras con Force Feedback para PS5 y PC.', 450000, 2], // Stock bajo
                    ['DualSense Edge', 'Control premium y personalizable para PS5.', 250000, 4],
                    ['Hori Fighting Stick Alpha', 'Arcade stick oficial para juegos de pelea en PS5 y PC.', 210000, 3], // Stock bajo
                ]
            ],
            [
                'nombre' => 'Periféricos',
                'descripcion' => 'Auriculares, teclados, mouse y más accesorios para PC y consolas.',
                'color' => 'F5A623', // Orange
                'subcategorias' => ['Teclados mecánicos', 'Mouse gamer', 'Auriculares', 'Micrófonos', 'Monitores', 'Sillas gamer'],
                'productos' => [
                    ['HyperX Cloud II Red', 'Auriculares gamer con sonido envolvente 7.1.', 120000, 15],
                    ['Teclado Mecánico Redragon Kumara K552', 'Teclado TKL con switches Outemu Red y retroiluminación RGB.', 55000, 25],
                    ['Mouse Logitech G203 Lightsync', 'Mouse gamer con sensor óptico de 8000 DPI.', 35000, 40],
                    ['Micrófono HyperX QuadCast S', 'Micrófono USB para streaming con RGB personalizable.', 180000, 5],
                    ['Monitor Gamer LG UltraGear 24 144Hz', 'Monitor de 24 pulgadas ideal para juegos competitivos.', 320000, 6],
                    ['Silla Gamer Corsair T3 Rush', 'Silla ergonómica de tela transpirable para largas sesiones.', 420000, 2], // Stock bajo
                    ['Mouse Razer DeathAdder V2', 'Mouse ergonómico líder en e-sports.', 75000, 10],
                    ['Teclado Razer Huntsman Mini', 'Teclado 60% con switches ópticos analógicos.', 135000, 8],
                ]
            ],
            [
                'nombre' => 'Accesorios',
                'descripcion' => 'Cables, fundas, memorias y complementos.',
                'color' => 'BD10E0', // Purple
                'subcategorias' => ['Cables y adaptadores', 'Cargadores', 'Fundas y estuches', 'Memorias y almacenamiento', 'Soportes'],
                'productos' => [
                    ['Funda de Viaje Nintendo Switch', 'Estuche rígido para proteger tu consola y cartuchos.', 25000, 30],
                    ['Cable HDMI 2.1 Ultra High Speed 2M', 'Ideal para sacar el máximo provecho a PS5 y Xbox Series.', 18000, 50],
                    ['WD Black SN850X 1TB con Disipador', 'SSD NVMe compatible y certificado para PS5.', 180000, 10],
                    ['Seagate Storage Expansion Card 1TB', 'Tarjeta de expansión de almacenamiento para Xbox Series X|S.', 220000, 5],
                    ['Estación de Carga DualSense', 'Cargador doble oficial para controles de PS5.', 45000, 15],
                    ['Soporte Vertical PS5 Slim', 'Soporte oficial para poner tu consola en posición vertical.', 35000, 20],
                    ['Grips KontrolFreek Galaxy', 'Protectores y extensores para joysticks de PS4/PS5.', 15000, 40],
                ]
            ],
            [
                'nombre' => 'Retro',
                'descripcion' => 'Consolas, juegos y repuestos de generaciones clásicas.',
                'color' => '9013FE', // Deep Purple
                'subcategorias' => ['Nintendo 64', 'Super Nintendo', 'Sega Genesis', 'PlayStation 1 y 2', 'Cartuchos', 'Repuestos retro'],
                'productos' => [
                    ['Consola Super Nintendo Classic Edition', 'Edición miniatura de la SNES con 21 juegos preinstalados.', 150000, 5],
                    ['Cartucho Super Mario 64 Original', 'Juego original para N64 en buen estado.', 65000, 1], // Stock bajo
                    ['Memory Card PS2 8MB Original', 'Tarjeta de memoria oficial para PlayStation 2.', 12000, 8],
                    ['Control Sega Genesis 6 Botones', 'Joystick alternativo para consolas Sega de 16 bits.', 15000, 15],
                    ['Consola Nintendo 64 Gris', 'Consola N64 original con cables y un joystick.', 200000, 2], // Stock bajo
                    ['Cartucho Pokémon Stadium N64', 'Juego de batallas Pokémon para Nintendo 64.', 55000, 1], // Stock bajo
                ]
            ],
            [
                'nombre' => 'Coleccionables',
                'descripcion' => 'Figuras, amiibos y merchandising para los verdaderos fans.',
                'color' => 'D0021B', // Red
                'subcategorias' => ['Figuras', 'Amiibo', 'Posters', 'Llaveros', 'Ropa y merch'],
                'productos' => [
                    ['Figura Funko Pop! Kratos (God of War)', 'Figura coleccionable de Kratos número 269.', 35000, 20],
                    ['Amiibo Link (Tears of the Kingdom)', 'Figura interactiva de Link para Nintendo Switch.', 45000, 10],
                    ['Lámpara Paladone PlayStation Icons', 'Lámpara ambiental con los clásicos símbolos de PS.', 40000, 15],
                    ['Llavero Hylian Shield Zelda', 'Llavero de metal de alta calidad del escudo Hylian.', 8000, 50],
                    ['Remera Oficial Xbox Logo', 'Remera de algodón negra con logo verde de Xbox.', 25000, 25],
                    ['Figura Nendoroid Doom Slayer', 'Figura articulada de Good Smile Company.', 95000, 3], // Stock bajo
                    ['Amiibo Bowser (Super Smash Bros)', 'Figura de colección interactiva.', 42000, 8],
                ]
            ],
        ];

        // 3 Productos sin imágenes
        $productosSinImagen = [
            'Cable HDMI 2.1 Ultra High Speed 2M',
            'Llavero Hylian Shield Zelda',
            'Control Sega Genesis 6 Botones',
        ];

        foreach ($categoriasData as $catData) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $catData['nombre']],
                ['descripcion' => $catData['descripcion']]
            );

            foreach ($catData['subcategorias'] as $subNombre) {
                Subcategoria::firstOrCreate(
                    ['nombre' => $subNombre, 'categoria_id' => $categoria->id]
                );
            }

            foreach ($catData['productos'] as $prodData) {
                $nombreProd = $prodData[0];
                
                $producto = Producto::firstOrCreate(
                    ['nombre' => $nombreProd],
                    [
                        'descripcion' => $prodData[1],
                        'precioUnitario' => $prodData[2],
                        'stock' => $prodData[3],
                        'categoria_id' => $categoria->id
                    ]
                );

                if (!in_array($nombreProd, $productosSinImagen)) {
                    // Generar imágenes si no tiene
                    if ($producto->imagenes()->count() === 0) {
                        $slug = Str::slug($nombreProd);
                        $color = $catData['color'];
                        $cantImagenes = rand(2, 3);
                        
                        for ($i = 1; $i <= $cantImagenes; $i++) {
                            // URL: https://placehold.co/600x600/{colorHex}/FFF?text={slug-del-producto}
                            // Reemplazamos los guiones por espacios en el texto para que se lea mejor en el placehold
                            $texto = urlencode(str_replace('-', ' ', $slug) . " $i");
                            $imagenUrl = "https://placehold.co/600x600/{$color}/FFF?text={$texto}";
                            $publicId = "demo/{$slug}-{$i}";

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
}
