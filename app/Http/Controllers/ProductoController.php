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
        $productos = Producto::with(['categoria', 'imagenes'])->paginate(10);

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

                        if ($producto->imagenes->isNotEmpty()) {
                            $html .= '<div class="col">';
                            $html .= '<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#imagenesModal' . $producto->id . '">
                                        <i class="fas fa-images mr-1"></i> Ver imágenes <span class="badge badge-light ml-1">' . $producto->imagenes->count() . '</span>
                                    </button>';

                            $html .= '
                            <div class="modal fade" id="imagenesModal' . $producto->id . '" tabindex="-1" role="dialog" aria-labelledby="imagenesModalLabel' . $producto->id . '" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-images mr-2"></i>Imágenes de ' . e($producto->nombre) . '
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <div id="carousel' . $producto->id . '" class="carousel slide" data-ride="carousel">
                                                <ol class="carousel-indicators">';
                                                foreach ($producto->imagenes as $index => $imagen) {
                                                    $html .= '<li data-target="#carousel' . $producto->id . '" data-slide-to="' . $index . '" ' . ($index === 0 ? 'class="active"' : '') . '></li>';
                                                }
                                                $html .= '</ol>
                                                <div class="carousel-inner">';
                                                    foreach ($producto->imagenes as $index => $imagen) {
                                                        $html .= '
                                                        <div class="carousel-item ' . ($index === 0 ? 'active' : '') . '">
                                                            <img src="' . e($imagen->url) . '" class="d-block w-100" alt="Imagen de ' . e($producto->nombre) . '">
                                                            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded py-1">
                                                                <p class="mb-0">Imagen ' . ($index + 1) . ' de ' . $producto->imagenes->count() . '</p>
                                                            </div>
                                                        </div>';
                                                    }
                                                $html .= '
                                                </div>
                                                <a class="carousel-control-prev" href="#carousel' . $producto->id . '" role="button" data-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="sr-only">Anterior</span>
                                                </a>
                                                <a class="carousel-control-next" href="#carousel' . $producto->id . '" role="button" data-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="sr-only">Siguiente</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                            $html .= '</div>';
                        } else {
                            $html .= '<div class="col"><span class="badge badge-secondary">Sin imagen</span></div>';
                        }

                        return $html;
                    }

            ])->render();
        }

        return view('producto.producto_listar', compact('productos',));
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
            // nombre
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',

            // descripcion
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',

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

        \Log::info('Imágenes recibidas:', $request->file('imagenes'));

        foreach ($request->file('imagenes') as $uploadedFile) {
            $timestamp = time();
            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');

            $signature = hash('sha256', "folder=productos&timestamp=$timestamp$apiSecret");

            $response = Http::withoutVerifying()
                ->asMultipart()
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                    [
                        'name'     => 'file',
                        'contents' => fopen($uploadedFile->getRealPath(), 'r'),
                        'filename' => $uploadedFile->getClientOriginalName(),
                    ],
                    ['name' => 'api_key', 'contents' => $apiKey],
                    ['name' => 'timestamp', 'contents' => $timestamp],
                    ['name' => 'folder', 'contents' => 'productos'],
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
