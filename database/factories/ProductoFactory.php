<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'precioUnitario' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'categoria_id' => \App\Models\Categoria::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Producto $producto) {
            if ($producto->categoria_id && !$producto->categorias()->where('categorias.id', $producto->categoria_id)->exists()) {
                $producto->categorias()->attach($producto->categoria_id);
            }
        });
    }

    public function conImagenes(int $count = 3): static
    {
        return $this->has(\App\Models\Imagen::factory()->count($count), 'imagenes');
    }
}
