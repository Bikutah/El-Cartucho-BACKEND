<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'descripcion',
        'precioUnitario',
        'stock',
        'categoria_id',
        'subcategoria_id',
    ];
    protected $casts = [
        'precioUnitario' => 'decimal:2',
        'stock' => 'integer',
    ];
    public function imagenes()
    {
        return $this->hasMany(Imagen::class);
    }
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
    public function subcategorias()
    {
        return $this->belongsToMany(Subcategoria::class, 'producto_subcategoria');
    }
    public function getPrimeraImagenAttribute()
    {
        return $this->imagenes()->first();
    }
}
