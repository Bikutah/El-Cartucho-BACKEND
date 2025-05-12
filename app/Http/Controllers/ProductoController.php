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
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('imagen');

        if ($request->hasFile('imagen')) {
            $uploadedFile = $request->file('imagen');
            $timestamp = time();

            $cloudName = config('cloudinary.cloud.cloud_name');
            $apiKey = config('cloudinary.cloud.api_key');
            $apiSecret = config('cloudinary.cloud.api_secret');

            $signature = hash('sha256', "folder=productos&timestamp=$timestamp$apiSecret");

            $response = Http::withoutVerifying() // ✅ Esto desactiva SSL check
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
                return back()->withErrors(['imagen' => 'Error al subir imagen a Cloudinary']);
            }

            $result = $response->json();

            $data['image_url'] = $result['secure_url'] ?? null;
            $data['image_public_id'] = $result['public_id'] ?? null;
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
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente'); 
    }
}
