<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\SubcategoriaController;
use App\Http\Controllers\ImagenController;

// Rutas de autenticación
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/api/producto/listar', [ProductoController::class, 'buscar']);

// Rutas protegidas
Route::middleware(['auth', AdminMiddleware::class])->group(function () {

    Route::get('/', [HomeController::class, 'home'])->name('home');

    Route::resource('categorias', CategoriaController::class);

    Route::resource('pedidos', PedidoController::class);

    # Productos
    Route::resource('productos', ProductoController::class);
    
    Route::get('/productos/{producto}/imagenes', [ProductoController::class, 'verImagenes'])->name('productos.imagenes');
    Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');


    Route::resource('subcategorias', SubcategoriaController::class);

    Route::delete('/imagenes/{imagen}', [ImagenController::class, 'destroy'])->name('imagenes.destroy');
    Route::post('/imagenes', [ImagenController::class, 'store'])->name('imagenes.store');

});



