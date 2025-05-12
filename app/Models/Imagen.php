<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    protected $table = 'imagenes';
    // Si usás Laravel 8+, indicá el fillable para proteger los campos
    protected $fillable = ['imagen_url', 'imagen_public_id', 'producto_id'];

    // Relación inversa: una imagen pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
