<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\CategoriaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Endpoints

#Mercado Pago
Route::post('/webhook/mercadopago', [WebhookController::class, 'handle']);

#Productos
Route::get('/producto/listar', [ProductoController::class, 'buscar']);
Route::get('/producto/{id}', [ProductoController::class, 'obtenerProductoConResource']);
Route::get('/productosRecientes', [ProductoController::class, 'obtenerProductosRecientes']);
Route::get('/productosMasVendidos', [ProductoController::class, 'obtenerProductosMasVendidos']);

#Pedidos
Route::post('/pedido/crear', [PedidoController::class, 'store']);

Route::get('/pedido/costo/{cp}', [PedidoController::class, 'calcularCostoEnvio']);

Route::get('/categorias', [CategoriaController::class, 'apiList']);
