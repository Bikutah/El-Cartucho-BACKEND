<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subcategoria extends Model
{
    use HasFactory;
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
