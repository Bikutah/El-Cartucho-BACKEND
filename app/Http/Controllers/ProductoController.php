<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ProductoResource;
use App\Http\Resources\ProductoDetalleResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BuscarProductosRequest;


class ProductoController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'nombre' => 'nullable|string|max:100',
            'stock' => 'nullable|integer|min:0',
            'categoria' => 'nullable|string|max:100',
        ]);

        session(['listado_url.productos' => url()->full()]);

        $query = Producto::with(['categoria', 'imagenes']);

        if ($request->filled('nombre')) {
            $patron = $this->normalizarYGenerarPatron($request->nombre);
            $query->whereRaw("LOWER(TRANSLATE(nombre, 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunAEIOUUN')) LIKE ?", [$patron]);
        }

        if ($request->filled('stock')) {
            $query->where('stock', $request->stock);
        }

        if ($request->filled('categoria')) {
            $patronCat = $this->normalizarYGenerarPatron($request->categoria);
            $query->whereHas('categoria', function ($q) use ($patronCat) {
                $q->whereRaw("LOWER(TRANSLATE(nombre, 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunAEIOUUN')) LIKE ?", [$patronCat]);
            });
        }

        $productos = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $productos,
                'columnas' => [
                    ['label' => 'Id'],
                    ['label' => 'Nombre'],
                    ['label' => 'Descripción', 'class' => 'd-none d-md-block'],
                    ['label' => 'Precio', 'class' => 'd-none d-md-block'],
                    ['label' => 'Stock'],
                    ['label' => 'Categoría'],
                    ['label' => 'Imágenes']
                ],
                'rutaEditar' => 'productos.edit',
                'rutaEliminar' => 'productos.destroy',
                'renderFila' => function ($producto) {
                    return '
                    <div class="table-cell">' . e($producto->id) . '</div>
                    <div class="table-cell nombre">
                        <span class="table-cell-label">Nombre:</span>
                        <span class="truncate-15 truncate-with-tooltip"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="' . e($producto->nombre) . '">'
                        . e($producto->nombre) .
                        '</span>
                    </div>
                    <div class="table-cell descripcion">
                        <span class="table-cell-label">Descripción:</span>
                        <span class="truncate-15 truncate-with-tooltip"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="' . e($producto->descripcion) . '">'
                        . e($producto->descripcion) .
                        '</span>
                    </div>
                    <div class="table-cell">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
                    <div class="table-cell">' . e($producto->stock) . '</div>
                    <div class="table-cell">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
                    <div class="table-cell">
                        <a href="' . route('productos.imagenes', $producto) . '" class="action-btn"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Ver imágenes">
                            <i class="fas fa-images"></i>
                            <span class="badge bg-light text-dark">' . count($producto->imagenes) . '</span>
                        </a>
                    </div>';
                }
            ])->render();
        }


        $categorias = Categoria::orderBy('nombre')->get();
        return view('producto.producto_listar', compact('productos', 'categorias'));
    }


    public function create()
    {
        $categorias = Categoria::with('subcategorias')->get();
        return view('producto.producto_crear', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'precioUnitario' => 'required|numeric',
            'stock' => 'required|integer',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategorias' => 'nullable|array|min:0',
            'subcategorias.*' => 'nullable|exists:subcategorias,id',
            'imagenes' => 'required|array|min:1|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser un texto válido.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'precioUnitario.required' => 'El precio unitario es obligatorio.',
            'precioUnitario.numeric' => 'El precio unitario debe ser un número válido.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'subcategorias.array' => 'Las subcategorías deben ser un array válido.',
            'subcategorias.*.exists' => 'Una o más subcategorías seleccionadas no son válidas.',
            'imagenes.required' => 'Debe subir al menos una imagen.',
            'imagenes.array' => 'Las imágenes deben ser proporcionadas en formato correcto.',
            'imagenes.min' => 'Debe subir al menos una imagen.',
            'imagenes.max' => 'No puede subir más de 5 imágenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp.',
            'imagenes.*.max' => 'Cada imagen no puede exceder los 2MB.',
        ]);

        $data = $request->except(['imagenes', 'subcategorias']);
        $producto = Producto::create($data);

        // Asociar subcategorías solo si existen y no están vacías
        if ($request->has('subcategorias') && !empty(array_filter($request->subcategorias))) {
            $producto->subcategorias()->sync(array_filter($request->subcategorias));
        }

        // ... resto del código para subir imágenes (sin cambios)
        $slugNombre = Str::slug($producto->nombre);
        $timestamp = time();

        foreach ($request->file('imagenes') as $index => $uploadedFile) {
            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');
            $folder = 'productos';
            $publicId = "{$slugNombre}_{$producto->id}_{$index}_{$timestamp}";

            $params_to_sign = "folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
            $signature = hash('sha256', $params_to_sign);

            $response = Http::asMultipart()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                [
                    'name' => 'file',
                    'contents' => fopen($uploadedFile->getRealPath(), 'r'),
                    'filename' => $uploadedFile->getClientOriginalName(),
                ],
                ['name' => 'api_key', 'contents' => $apiKey],
                ['name' => 'timestamp', 'contents' => $timestamp],
                ['name' => 'folder', 'contents' => $folder],
                ['name' => 'public_id', 'contents' => $publicId],
                ['name' => 'signature', 'contents' => $signature],
            ]);

            if (!$response->successful()) {
                return back()->withErrors(['imagenes' => 'Error al subir una de las imágenes.']);
            }

            $result = $response->json();

            $producto->imagenes()->create([
                'imagen_url' => $result['secure_url'],
                'imagen_public_id' => $result['public_id'],
            ]);
        }

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente');
    }


    public function show($id)
    {
        return view('productos.show', compact('id'));
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::with('subcategorias')->get();
        $producto->load('subcategorias');
        return view('producto.producto_editar', compact('producto', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'precioUnitario' => 'required|numeric',
            'stock' => 'required|integer',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategorias' => 'nullable|array|min:0',
            'subcategorias.*' => 'nullable|exists:subcategorias,id',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser un texto válido.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'precioUnitario.required' => 'El precio unitario es obligatorio.',
            'precioUnitario.numeric' => 'El precio unitario debe ser un número válido.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'subcategorias.array' => 'Las subcategorías deben ser un array válido.',
            'subcategorias.*.exists' => 'Una o más subcategorías seleccionadas no son válidas.',
            'imagenes.array' => 'Las imágenes deben ser proporcionadas en formato correcto.',
            'imagenes.max' => 'No puede subir más de 5 imágenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp.',
            'imagenes.*.max' => 'Cada imagen no puede exceder los 2MB.',
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update($request->except('subcategorias', 'imagenes'));

        // Sincronizar subcategorías (permite array vacío)
        if ($request->has('subcategorias')) {
            $subcategorias = array_filter($request->subcategorias ?? []);
            $producto->subcategorias()->sync($subcategorias);
        } else {
            $producto->subcategorias()->sync([]);
        }

        // Manejar imágenes si se suben nuevas
        if ($request->hasFile('imagenes')) {
            // Aquí podrías agregar lógica para manejar nuevas imágenes si es necesario
        }

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente');
    }

    private function normalizarYGenerarPatron(?string $texto): string
    {
        if (empty($texto)) {
            return '%';
        }

        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $remplazos = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
            'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
        ];
        $texto = strtr($texto, $remplazos);
        $palabras = array_filter(explode(' ', preg_replace('/[^a-z0-9]+/u', ' ', $texto)));

        return '%' . implode('%', $palabras) . '%';
    }

    public function buscar(BuscarProductosRequest $request)
    {
        $validated = $request->validated();

        $query = Producto::with(['categoria', 'subcategorias', 'imagenes']);

        if (!empty($validated['q'])) {
            $patron = $this->normalizarYGenerarPatron($validated['q']);
            $query->where(function ($sub) use ($patron) {
                $sub->whereRaw("LOWER(TRANSLATE(nombre, 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunAEIOUUN')) LIKE ?", [$patron])
                    ->orWhereRaw("LOWER(TRANSLATE(descripcion, 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunAEIOUUN')) LIKE ?", [$patron]);
            });
        }

        if (!empty($validated['categoria_id'])) {
            $query->where('categoria_id', $validated['categoria_id']);
        }

        if (!empty($validated['subcategorias'])) {
            $subcategorias = $validated['subcategorias'];
            $query->whereHas('subcategorias', function ($q) use ($subcategorias) {
                $q->whereIn('subcategoria_id', $subcategorias);
            });
        }

        if (isset($validated['precio_min'])) {
            $query->where('precioUnitario', '>=', $validated['precio_min']);
        }

        if (isset($validated['precio_max'])) {
            $query->where('precioUnitario', '<=', $validated['precio_max']);
        }

        $orden = $validated['orden'] ?? 'created_at';
        $columnMap = [
            'nombre' => 'nombre',
            'precio' => 'precioUnitario',
            'created_at' => 'created_at',
        ];
        $sortColumn = $columnMap[$orden] ?? 'created_at';
        $dir = $validated['dir'] ?? 'desc';

        $query->orderBy($sortColumn, $dir);

        $perPage = $validated['per_page'] ?? 8;

        $productos = $query->paginate($perPage)->withQueryString();

        return ProductoResource::collection($productos);
    }

    public function obtenerProductosRecientes()
    {
        $productos = Producto::with(['categoria', 'imagenes'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return ProductoResource::collection($productos);
    }

    public function obtenerProductosMasVendidos()
    {
        $productos = Producto::with(['categoria', 'imagenes'])
            ->select('productos.*', DB::raw('SUM(detalle_pedido.cantidad) as total_vendido'))
            ->join('detalle_pedido', 'productos.id', '=', 'detalle_pedido.producto_id')
            ->groupBy('productos.id')
            ->orderByDesc('total_vendido')
            ->take(6) 
            ->get();

        return ProductoResource::collection($productos);
    }

    public function obtenerProductoConResource($id)
    {
        try {
            $producto = Producto::with(['categoria', 'subcategorias', 'imagenes'])
                ->findOrFail($id);

            return response()->json(
                (new ProductoDetalleResource($producto))->toArray(request())
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
                'error' => 'El producto con ID ' . $id . ' no existe.'
            ], 404);
        }
    }

    public function verImagenes(Producto $producto)
    {
        $imagenes = $producto->imagenes;
        return view('producto.producto_imagenes', compact('producto', 'imagenes'));
    }

    public function destroy(Producto $producto)
    {
        // Eliminar imágenes de Cloudinary
        foreach ($producto->imagenes as $imagen) {
            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');

            $timestamp = time();
            $publicId = $imagen->imagen_public_id;

            $params_to_sign = "public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
            $signature = hash('sha256', $params_to_sign);

            $response = Http::asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
                'api_key'    => $apiKey,
                'timestamp'  => $timestamp,
                'public_id'  => $publicId,
                'signature'  => $signature,
            ]);

            // Podés validar $response si querés agregar control de errores
        }

        // Eliminar imágenes en base de datos
        $producto->imagenes()->delete();

        // Eliminar el producto
        $producto->delete();

        // Si es una petición AJAX, devolvé JSON
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Producto eliminado correctamente.']);
        }

        // Si no, redireccioná como siempre
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }
}
