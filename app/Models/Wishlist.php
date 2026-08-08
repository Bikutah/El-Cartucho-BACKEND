<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'wishlist';

    protected $fillable = ['firebase_uid', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class)->with('imagenes');
    }
}
