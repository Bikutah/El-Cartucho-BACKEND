<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedidos = \App\Models\Pedido::orderBy('id', 'desc')->take(5)->get();
foreach($pedidos as $p) {
    echo "ID: " . $p->id . " | Nombre: " . $p->cliente_nombre . " | Apellido: " . $p->cliente_apellido . "\n";
}
