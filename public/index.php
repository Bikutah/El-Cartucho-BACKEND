<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Ejecutar migraciones automáticamente si es necesario
try {
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    if (!Illuminate\Support\Facades\Schema::hasTable('migrations')) {
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    }
} catch (\Throwable $e) {
    error_log("Error al correr migraciones: " . $e->getMessage());
}

// Manejar la solicitud...
$app->handleRequest(Request::capture());
