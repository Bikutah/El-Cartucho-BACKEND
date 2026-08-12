<?php

namespace Database\Factories;

use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firebase_uid'  => $this->faker->uuid(),
            'total'         => $this->faker->randomFloat(2, 100, 5000),
            'estado'        => 'pendiente',
            'estado_pago'   => 'pendiente',
            'estado_envio'  => null,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Pedido $pedido) {
            if ($pedido->estado === 'pagado' && $pedido->estado_pago === 'pendiente') {
                $pedido->estado_pago = 'pagado';
            } elseif ($pedido->estado === 'cancelado' && $pedido->estado_pago === 'pendiente') {
                $pedido->estado_pago = 'rechazado';
            } elseif ($pedido->estado_pago === 'pagado' && $pedido->estado !== 'pagado') {
                $pedido->estado = 'pagado';
            } elseif (in_array($pedido->estado_pago, ['rechazado', 'expirado', 'reembolsado'], true) && $pedido->estado !== 'cancelado') {
                $pedido->estado = 'cancelado';
            }
        });
    }
}
