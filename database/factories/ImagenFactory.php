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
        return [
            'producto_id' => \App\Models\Producto::factory(),
            'imagen_url' => $this->faker->imageUrl(),
            'imagen_public_id' => $this->faker->uuid(),
        ];
    }
}
