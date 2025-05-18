<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


#Mercado Pago
Route::post('/webhook/mercadopago', [WebhookController::class, 'handle']);

#Productos
Route::get('/producto/listar', [ProductoController::class, 'buscar']);
