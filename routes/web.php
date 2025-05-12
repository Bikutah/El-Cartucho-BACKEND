<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SubcategoriaController;

// Rutas de autenticación
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware(['auth', AdminMiddleware::class])->group(function () {

    Route::get('/', [HomeController::class, 'home'])->name('home');

    Route::resource('categorias', CategoriaController::class);

    Route::resource('productos', ProductoController::class);

    Route::resource('subcategorias', SubcategoriaController::class);
});




