<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HolaController extends Controller
{
    public function saludo()
    {
        return 'Hola Mundo desde un controlador!';
    }
}
