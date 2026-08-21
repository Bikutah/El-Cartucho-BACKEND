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
use App\Http\Controllers\CronController;
use App\Http\Controllers\PushNotificationController;

// ─── Sin autenticación ───────────────────────────────────────────────────────

#Mercado Pago
Route::post('/webhook/mercadopago', [WebhookController::class, 'handle']);

#Cron
Route::get('/cron/liberar-vencidos', [CronController::class, 'liberarPedidosVencidos']);

#Productos
Route::get('/producto/listar', [ProductoController::class, 'buscar']);
Route::get('/producto/{id}', [ProductoController::class, 'obtenerProductoConResource']);
Route::get('/productosRecientes', [ProductoController::class, 'obtenerProductosRecientes']);
Route::get('/productosMasVendidos', [ProductoController::class, 'obtenerProductosMasVendidos']);

#Categorias
Route::get('/categorias', [CategoriaController::class, 'apiList']);

#Envío (cálculo de costo, no requiere usuario)
Route::get('/pedido/costo/{cp}', [PedidoController::class, 'calcularCostoEnvio']);

// ─── Token verificado (sin requerir usuario local) ───────────────────────────
// GET /profile tolera que el usuario no exista aún; el controller hace firstOrCreate.
Route::middleware(['firebase.token'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'getProfile']);
    // Clave pública VAPID — necesaria antes de que el usuario exista localmente
    Route::get('/push/vapid-key', [PushNotificationController::class, 'vapidKey']);
});

// ─── Token verificado + usuario local existente ──────────────────────────────
Route::middleware(['firebase.token', 'firebase.user'])->group(function () {
    #Perfil
    Route::post('/profile', [ProfileController::class, 'updateProfile']);

    #Pedidos
    Route::post('/pedido/crear', [PedidoController::class, 'store']);
    Route::get('/pedido/pendiente', [PedidoController::class, 'obtenerPedidoPendiente']);
    Route::get('/mis-pedidos', [PedidoController::class, 'misPedidos']);
    Route::get('/mis-pedidos/{id}', [PedidoController::class, 'detallePedidoCliente']);
    Route::post('/pedido/{id}/reintentar-pago', [PedidoController::class, 'reintentarPago']);
    Route::post('/pedido/{id}/cancelar', [PedidoController::class, 'cancelarPedido']);
    Route::get('/pedido/{id}/estado', [PedidoController::class, 'obtenerEstado']);

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

    #Push Notifications
    Route::post('/push/subscribe',   [PushNotificationController::class, 'subscribe']);
    Route::delete('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe']);
    Route::get('/push/status',       [PushNotificationController::class, 'status']);
});
