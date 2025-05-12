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
        'categoria_id'
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
}
