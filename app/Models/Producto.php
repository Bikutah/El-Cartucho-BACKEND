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
        'imagen',
        'categoria_id'
    ];
    protected $casts = [
        'precioUnitario' => 'decimal:2',
        'stock' => 'integer',
    ];
}
