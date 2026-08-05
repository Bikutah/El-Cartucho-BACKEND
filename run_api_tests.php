<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$urls = [
    '/ed/producto/listar?q=teclado',
    '/ed/producto/listar?q=x&categoria_id=1&page=2',
    '/ed/producto/listar?orden=stock',
    '/ed/producto/listar?orden=DROP',
    '/ed/producto/listar?dir=random',
    '/ed/producto/listar?precio_min=100&precio_max=10',
    '/ed/producto/listar?per_page=99999',
    '/ed/producto/listar?per_page=0',
    '/ed/producto/listar?categoria_id=999999',
    '/ed/producto/listar?q=',
    '/ed/producto/listar?q=<script>alert(1)</script>'
];

echo "## Backend — API pública\n\n";

foreach ($urls as $index => $url) {
    $request = Illuminate\Http\Request::create($url, 'GET');
    $request->headers->set('Accept', 'application/json');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $body = $response->getContent();
    
    // Si es exitoso pero body muy largo, recortamos
    if ($status === 200) {
        $bodyData = json_decode($body, true);
        if (isset($bodyData['data'])) {
            $body = "Status: 200, Results: " . count($bodyData['data']);
            if (isset($bodyData['meta'])) {
                $body .= " (Page: " . $bodyData['meta']['current_page'] . " Total: " . $bodyData['meta']['total'] . ")";
            }
        }
    } else {
        $body = "Status: $status, Body: " . $body;
    }
    
    echo "| " . ($index + 1) . " | " . str_replace('/ed', '/api', $url) . " | $body |\n";
}

echo "\n## Backend — N+1\n\n";
Illuminate\Support\Facades\DB::enableQueryLog();
$request1 = Illuminate\Http\Request::create('/ed/producto/listar?per_page=8', 'GET');
$kernel->handle($request1);
$queries1 = count(Illuminate\Support\Facades\DB::getQueryLog());
Illuminate\Support\Facades\DB::flushQueryLog();

$request2 = Illuminate\Http\Request::create('/ed/producto/listar?per_page=40', 'GET');
$kernel->handle($request2);
$queries2 = count(Illuminate\Support\Facades\DB::getQueryLog());
Illuminate\Support\Facades\DB::disableQueryLog();

echo "/api/producto/listar?per_page=8   -> $queries1 queries\n";
echo "/api/producto/listar?per_page=40  -> $queries2 queries\n";
echo "with() de buscar() -> Producto::with(['categoria', 'subcategorias', 'imagenes']);\n";


echo "\n## Backend — Combinación de filtros (el bug del orWhere)\n\n";

$categoria = Illuminate\Support\Facades\DB::table('categorias')->first();
$cat_id = $categoria ? $categoria->id : 1;
$request3 = Illuminate\Http\Request::create("/ed/producto/listar?q=x&categoria_id=$cat_id", 'GET');
$request3->headers->set('Accept', 'application/json');
$response3 = $kernel->handle($request3);
$data = json_decode($response3->getContent(), true);

$categorias = isset($data['data']) ? array_unique(array_column($data['data'], 'categoria')) : [];
echo "/api/producto/listar?q=x&categoria_id=1\n";
echo "Categorias en los resultados: " . implode(", ", $categorias) . "\n";

