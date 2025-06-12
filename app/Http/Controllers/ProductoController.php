<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ProductoResource;
use Illuminate\Support\Str;


class ProductoController extends Controller
{

    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'imagenes']);

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        if ($request->filled('stock')) {
            $query->where('stock', 'like', '%' . $request->stock . '%');
        }

        if ($request->filled('categoria')) {
            $query->whereHas('categoria', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->categoria . '%');
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
            'subcategorias' => 'nullable|array', // Validar que subcategorias sea un arreglo
            'subcategorias.*' => 'exists:subcategorias,id', // Cada subcategoría debe existir
            'imagenes' => 'required|array|min:1|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            // ... otros mensajes de error ...
            'subcategorias.array' => 'Las subcategorías deben ser un arreglo válido.',
            'subcategorias.*.exists' => 'Una o más subcategorías seleccionadas no son válidas.',
        ]);

        $data = $request->except(['imagenes', 'subcategorias']);
        $producto = Producto::create($data);

        // Asociar subcategorías
        if ($request->filled('subcategorias')) {
            $producto->subcategorias()->sync($request->subcategorias);
        }

        // Código para subir imágenes a Cloudinary (sin cambios)
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
            'subcategorias' => 'nullable|array',
            'subcategorias.*' => 'exists:subcategorias,id',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            // ... otros mensajes de error ...
            'subcategorias.array' => 'Las subcategorías deben ser un arreglo válido.',
            'subcategorias.*.exists' => 'Una o más subcategorías seleccionadas no son válidas.',
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update($request->except('subcategorias'));

        $producto->subcategorias()->sync($request->subcategorias ?? []);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente');
    }

    public function buscar(Request $request)
    {
        $productos = Producto::with(['categoria'])->paginate(8);

        return ProductoResource::collection($productos);
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
