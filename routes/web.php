<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoriaController;

Route::get('/', [HomeController::class, 'home'])->name('home');

Route::resource('categorias', CategoriaController::class);
