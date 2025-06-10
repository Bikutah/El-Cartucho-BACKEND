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
use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\EstadisticasController;

// Rutas de autenticación
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware(['auth', AdminMiddleware::class])->group(function () {

    Route::get('/', [HomeController::class, 'home'])->name('home');

    Route::resource('categorias', CategoriaController::class);

    Route::resource('pedidos', PedidoController::class);
    Route::get('/pedidos/{id}/imprimir', [PedidoController::class, 'imprimir'])->name('pedidos.imprimir');

    # Productos
    Route::resource('productos', ProductoController::class);

    Route::get('/productos/{producto}/imagenes', [ProductoController::class, 'verImagenes'])->name('productos.imagenes');
    #Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');


    Route::resource('subcategorias', SubcategoriaController::class);

    Route::delete('/imagenes/{imagen}', [ImagenController::class, 'destroy'])->name('imagenes.destroy');
    Route::post('/imagenes', [ImagenController::class, 'store'])->name('imagenes.store');

    // Rutas para las estadísticas principales
    Route::get('/estadisticas/ventas-mensuales', [EstadisticasController::class, 'ventasMensuales']);
    Route::get('/estadisticas/distribucion-productos', [EstadisticasController::class, 'distribucionProductos']);
    Route::get('/estadisticas/comparativa-anual', [EstadisticasController::class, 'comparativaAnual']);
    Route::get('/estadisticas/categorias', [EstadisticasController::class, 'categorias']);

    // Rutas adicionales para más estadísticas
    Route::get('/estadisticas/productos-mas-vendidos', [EstadisticasController::class, 'productosMasVendidos']);
    Route::get('/estadisticas/resumen-general', [EstadisticasController::class, 'resumenGeneral']);
    Route::get('/estadisticas/ventas-por-estado', [EstadisticasController::class, 'ventasPorEstado']);
});
