<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HolaController;

Route::get('/', [HolaController::class, 'saludo']);
