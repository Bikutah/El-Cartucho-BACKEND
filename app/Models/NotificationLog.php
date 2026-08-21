<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'titulo',
        'mensaje',
        'url',
        'enviadas',
        'exitosas',
        'fallidas',
        'tipo',
    ];
}
