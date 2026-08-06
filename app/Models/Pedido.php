<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = ['firebase_uid', 'estado', 'mercado_pago_id', 'total', 'expira_at'];

    protected $casts = [
        'expira_at' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }
}
