<?php

namespace App\Http\Controllers;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Http;


class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')->paginate(10);

        if (request()->ajax()) {
            return view('base.partials.tabla', [
                'items' => $productos,
                'columnas' => ['Id', 'Nombre', 'Descripción', 'PrecioUnitario', 'Stock', 'Categoría', 'Imagen'],
                'rutaEditar' => 'productos.edit',
                'renderFila' => function ($producto) {
                    $html = '
                        <div class="col">' . e($producto->id) . '</div>
                        <div class="col">' . e($producto->nombre) . '</div>
                        <div class="col">' . e($producto->descripcion) . '</div>
                        <div class="col">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
                        <div class="col">' . e($producto->stock) . '</div>
                        <div class="col">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
                    ';

                    if (!empty($producto->image_url)) {
                        $html .= '<div class="col"><img src="' . e($producto->image_url) . '" alt="Imagen" style="max-width: 70px; max-height: 70px;"></div>';
                    } else {
                        $html .= '<div class="col">Sin imagen</div>';
                    }

                    return $html;
                }
            ])->render();
        }

        return view('producto.producto_listar', compact('productos'));
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
            'descripcion' => 'required|string|max:255',
            'precioUnitario' => 'required|numeric',
            'stock' => 'required|integer',
            'categoria_id' => 'required|exists:categorias,id',
            'imagenes' => 'required|array|min:1|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'imagenes.required' => 'Debes subir al menos una imagen.',
            'imagenes.min' => 'Debes subir al menos una imagen.',
            'imagenes.max' => 'No podés subir más de 5 imágenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'Las imágenes deben ser jpeg, png, jpg o gif.',
            'imagenes.*.max' => 'Cada imagen no puede superar los 2MB.',
        ]);

        \Log::info('Formulario validado con éxito');


        $data = $request->except('imagenes');
        $producto = Producto::create($data);

        // Subir de 1 a 5 imágenes a Cloudinary
        foreach ($request->file('imagenes') as $uploadedFile) {
            $timestamp = time();
            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');

            $signature = hash('sha256', "folder=productos&timestamp=$timestamp$apiSecret");

            $response = Http::withoutVerifying()
                ->attach(
                    'file',
                    file_get_contents($uploadedFile->getRealPath()),
                    $uploadedFile->getClientOriginalName()
                )
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                    'api_key'    => $apiKey,
                    'timestamp'  => $timestamp,
                    'folder'     => 'productos',
                    'signature'  => $signature,
                ]);

            if (!$response->successful()) {
                return back()->withErrors(['imagenes' => 'Error al subir una de las imágenes.']);
            }

            $result = $response->json();

            // Guardar en tabla imagenes
            $producto->imagenes()->create([
                'imagen_url' => $result['secure_url'],
                'imagen_public_id' => $result['public_id'],
            ]);
        }

        Producto::create($data);

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
            'descripcion' => 'required|string|max:255',
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
}
