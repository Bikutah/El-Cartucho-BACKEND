<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoEstadoActualizado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Pedido  $pedido,
        public readonly string  $tipo,           // 'pago' | 'envio'
        public readonly ?string $estadoAnterior,
        public readonly string  $estadoNuevo,
        public readonly string  $origen,
        public readonly ?int    $userId,
    ) {}
}
