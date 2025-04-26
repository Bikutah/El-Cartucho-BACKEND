<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HolaController;
use App\Http\Controllers\CategoriaController;

Route::get('/', [HolaController::class, 'saludo']);

Route::resource('categorias', CategoriaController::class);
