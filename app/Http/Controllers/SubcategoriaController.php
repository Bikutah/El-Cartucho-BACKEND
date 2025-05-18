<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcategoria;
use App\Models\Categoria;

class SubcategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Subcategoria::with('categoria');

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $subcategorias = $query->paginate(10)->withQueryString();
        $categorias = Categoria::orderBy('nombre')->get();

        if ($request->ajax()) {
            return view('base.partials.tabla', [
                'items' => $subcategorias,
                'columnas' => ['Id', 'Nombre', 'Categoría'],
                'rutaEditar' => 'subcategorias.edit',
                'renderFila' => fn($subcategoria) => '
                    <div class="col">' . e($subcategoria->id) . '</div>
                    <div class="col">' . e($subcategoria->nombre) . '</div>
                    <div class="col">' . e(optional($subcategoria->categoria)->nombre ?? 'Sin Categoría') . '</div>
                '
            ])->render();
        }

        return view('subcategoria.subcategoria_listar', compact('subcategorias', 'categorias'));
    }


    public function create()
    {
        $categorias = Categoria::all();
        return view('subcategoria.subcategoria_crear', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:subcategorias,nombre',
            'categoria_id' => 'required|exists:categorias,id'
        ], [
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una subcategoría con ese nombre.',

            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
        ]);


        Subcategoria::create($request->all());

        return redirect()->route('subcategorias.index')->with('success', 'Subcategoría creada correctamente');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Subcategoria $subcategoria)
    {
        $categorias = Categoria::all();
        if ($subcategoria->categoria_id) {
            $subcategoria->categoria = Categoria::find($subcategoria->categoria_id);
        } else {
            $subcategoria->categoria = null;
        }
        return view('subcategoria.subcategoria_editar', compact('subcategoria','categorias'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:subcategorias,nombre',
            'categoria_id' => 'required|exists:categorias,id'
        ], [
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una subcategoría con ese nombre.',

            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
        ]);

        $subcategoria = Subcategoria::findOrFail($id);
        $subcategoria->update($request->all());

        return redirect()->route('subcategorias.index')->with('success', 'Subcategoría actualizada correctamente');
    }

    public function destroy(string $id)
    {
        //
    }
}
