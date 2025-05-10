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
        'image_url', 
        'image_public_id',
        'categoria_id'
    ];
    protected $casts = [
        'precioUnitario' => 'decimal:2',
        'stock' => 'integer',
    ];
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
