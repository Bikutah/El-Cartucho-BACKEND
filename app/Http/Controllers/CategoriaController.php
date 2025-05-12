<?php

namespace App\Http\Controllers;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::paginate(10); 

        if (request()->ajax()) {
            return view('base.partials.tabla', [
                'items' => $categorias,
                'columnas' => ['Id', 'Nombre', 'Descripción'],
                'rutaEditar' => 'categorias.edit',
                'renderFila' => fn($categoria) => '
                    <div class="col">' . e($categoria->id) . '</div>
                    <div class="col">' . e($categoria->nombre) . '</div>
                    <div class="col">' . e($categoria->descripcion) . '</div>
                '
            ])->render();
        }

        return view('categoria.categoria_listar', compact('categorias'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categoria.categoria_crear');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
            'descripcion' => 'required|string'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',

            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
        ]);

        Categoria::create($request->all());

        return redirect()->route('categorias.index')->with('success', 'Categoría creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria)
    {
        return view('categoria.categoria_editar', compact('categoria'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
            'descripcion' => 'required|string'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',

            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
        ]);

        $categoria->update($request->all());

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
