<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = ['firebase_uid', 'estado', 'mercado_pago_id', 'total'];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }
}
