<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'firebase_uid',
        'email',
        'domicilio',
        'ciudad',
        'codigo_postal',
        'costo_envio',
        'zona_envio_id',
        'estado',
        'mercado_pago_id',
        'mercado_pago_preference_id',
        'total',
        'expira_at',
    ];

    protected $casts = [
        'costo_envio' => 'decimal:2',
        'expira_at' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zonaEnvio()
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_envio_id');
    }
}
