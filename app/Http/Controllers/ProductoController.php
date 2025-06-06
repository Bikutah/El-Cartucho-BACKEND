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
        $categorias = Categoria::all();
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
            'imagenes' => 'required|array|min:1|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            // nombre
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',

            // descripcion
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',

            // precioUnitario
            'precioUnitario.required' => 'El precio unitario es obligatorio.',
            'precioUnitario.numeric' => 'El precio unitario debe ser un número.',

            // stock
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',

            // categoria
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            // imágenes
            'imagenes.required' => 'Debes subir al menos una imagen.',
            'imagenes.array' => 'Las imágenes deben ser un arreglo válido.',
            'imagenes.min' => 'Debes subir al menos una imagen.',
            'imagenes.max' => 'No podés subir más de 5 imágenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'Las imágenes deben ser de tipo jpeg, png, jpg o gif.',
            'imagenes.*.max' => 'Cada imagen no puede superar los 2MB.',
        ]);

        $data = $request->except('imagenes');

        $producto = Producto::create($data);

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
                    'name'     => 'file',
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
        $categorias = Categoria::all();
        if ($producto->categoria_id) {
            $producto->categoria = Categoria::find($producto->categoria_id);
        } else {
            $producto->categoria = null;
        }
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
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'precioUnitario.required' => 'El precio unitario es obligatorio.',
            'precioUnitario.numeric' => 'El precio debe ser un número.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo jpeg, png, jpg o gif.',
            'imagen.max' => 'La imagen no puede superar los 2MB.',
            'imagen.required' => 'La imagen es obligatoria.',
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente');
    }

    public function buscar(Request $request)
    {
        $productos = Producto::with(['categoria'])->paginate(10);

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

            // Opcional: verificar respuesta y loguear si falla
        }

        // Eliminar imágenes en base de datos
        $producto->imagenes()->delete();

        // Eliminar el producto
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }
    
}


