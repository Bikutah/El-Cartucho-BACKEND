<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoHistorialEstado extends Model
{
    use HasFactory;

    protected $table = 'pedido_historial_estados';

    public $timestamps = false;

    protected $fillable = [
        'pedido_id',
        'tipo',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'origen',
        'observacion',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
