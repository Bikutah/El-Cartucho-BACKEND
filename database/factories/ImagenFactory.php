<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Imagen>
 */
class ImagenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;
        $id = $counter++;
        return [
            'producto_id' => \App\Models\Producto::factory(),
            'imagen_url' => "https://picsum.photos/seed/img{$id}/600/600",
            'imagen_public_id' => "seed/img-{$id}",
        ];
    }
}
