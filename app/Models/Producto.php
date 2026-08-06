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
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_producto');
    }
    public function getPrimeraImagenAttribute()
    {
        $first = $this->imagenes->first();
        if ($first) {
            return $first;
        }
        $stockUrl = config('app.stock_image_url', '/placeholder.svg');
        return (object) [
            'imagen_url' => $stockUrl,
            'imagen_public_id' => null,
        ];
    }
}
