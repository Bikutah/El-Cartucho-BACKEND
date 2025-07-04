<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_subcategoria');
    }
}
