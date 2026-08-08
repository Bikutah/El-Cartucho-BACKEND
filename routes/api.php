<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\WishlistController;

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
Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']);

#Categorias
Route::get('/categorias', [CategoriaController::class, 'apiList']);

#Perfil
Route::get('/profile', [ProfileController::class, 'getProfile']);
Route::post('/profile', [ProfileController::class, 'updateProfile']);

#Carrito
Route::get('/carrito', [CarritoController::class, 'index']);
Route::post('/carrito', [CarritoController::class, 'upsert']);
Route::delete('/carrito/{productoId}', [CarritoController::class, 'removeItem']);
Route::delete('/carrito', [CarritoController::class, 'clear']);

#Wishlist
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
Route::get('/wishlist/check/{productoId}', [WishlistController::class, 'check']);
Route::delete('/wishlist/{productoId}', [WishlistController::class, 'remove']);
