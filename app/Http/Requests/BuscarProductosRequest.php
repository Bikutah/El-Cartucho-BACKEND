<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuscarProductosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            // # DEPRECADO: eliminar en Pasada C
            'categoria_id' => 'nullable|integer|exists:categorias,id',
            'categorias' => 'nullable|array',
            'categorias.*' => 'integer|exists:categorias,id',
            'subcategorias' => 'nullable|array',
            'subcategorias.*' => 'integer|exists:subcategorias,id',
            'precio_min' => 'nullable|numeric|min:0',
            'precio_max' => 'nullable|numeric|gte:precio_min',
            'orden' => ['nullable', 'string', Rule::in(['nombre', 'precio', 'created_at'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,48',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.string' => 'El término de búsqueda debe ser un texto válido.',
            'q.max' => 'El término de búsqueda no puede exceder los 100 caracteres.',
            'categoria_id.integer' => 'La categoría debe ser un número entero.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'categorias.array' => 'Las categorías deben ser proporcionadas en formato correcto.',
            'categorias.*.integer' => 'El ID de la categoría debe ser un número entero.',
            'categorias.*.exists' => 'Una o más categorías seleccionadas no son válidas.',
            'subcategorias.array' => 'Las subcategorías deben ser proporcionadas en formato correcto.',
            'subcategorias.*.integer' => 'El ID de la subcategoría debe ser un número entero.',
            'subcategorias.*.exists' => 'Una o más subcategorías seleccionadas no son válidas.',
            'precio_min.numeric' => 'El precio mínimo debe ser un número válido.',
            'precio_min.min' => 'El precio mínimo no puede ser menor a 0.',
            'precio_max.numeric' => 'El precio máximo debe ser un número válido.',
            'precio_max.gte' => 'El precio máximo debe ser mayor o igual al precio mínimo.',
            'orden.in' => 'El campo de ordenamiento no es válido.',
            'dir.in' => 'La dirección de ordenamiento debe ser asc o desc.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser al menos 1.',
            'per_page.integer' => 'La cantidad de elementos por página debe ser un entero.',
            'per_page.between' => 'La cantidad por página debe estar entre 1 y 48.',
        ];
    }
}
