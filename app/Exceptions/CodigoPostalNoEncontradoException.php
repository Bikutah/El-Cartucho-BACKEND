<?php

namespace App\Exceptions;

use Exception;

class CodigoPostalNoEncontradoException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => 'El código postal ingresado no es valido.'
        ], 418);
    }
}
