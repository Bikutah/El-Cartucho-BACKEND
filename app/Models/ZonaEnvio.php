<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaEnvio extends Model
{
    use HasFactory;

    protected $table = 'zonas_envio';

    protected $fillable = [
        'nombre',
        'cp_desde',
        'cp_hasta',
        'costo',
        'activa',
        'orden',
    ];

    protected $casts = [
        'cp_desde' => 'integer',
        'cp_hasta' => 'integer',
        'costo'    => 'decimal:2',
        'activa'   => 'boolean',
        'orden'    => 'integer',
    ];

    /**
     * Resuelve la zona de envío correspondiente para un código postal.
     *
     * @param string $cp
     * @return self|null
     */
    public static function paraCodigoPostal(string $cp): ?self
    {
        if (!preg_match('/^\D*(\d{4})\D*$/', $cp, $matches)) {
            return null;
        }

        $cpNum = (int) $matches[1];

        return self::where('activa', true)
            ->where('cp_desde', '<=', $cpNum)
            ->where('cp_hasta', '>=', $cpNum)
            ->orderBy('orden', 'asc')
            ->first();
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'zona_envio_id');
    }
}
