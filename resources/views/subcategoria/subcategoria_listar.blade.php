@extends('base.listar')

@php
    $titulo = 'Listado de Subcategorías';
    $rutaCrear = 'subcategorias.create';
    $rutaEditar = 'subcategorias.edit';
    
    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Categoría']
    ];
    
    $items = $subcategorias;

    $filtros = [
        ['name' => 'nombre', 'placeholder' => 'Buscar por nombre'],
        [
            'name' => 'categoria_id',
            'placeholder' => 'Filtrar por categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'id')->toArray(),
        ]
    ];

    $renderFila = function($subcategoria) {
        return '
            <div class="table-cell">
                <span class="table-cell-label">Id:</span>
                <span>' . e($subcategoria->id) . '</span>
            </div>
            <div class="table-cell nombre">
                <span class="table-cell-label">Nombre:</span>
                <span class="truncate-15 truncate-with-tooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . e($subcategoria->nombre) . '">' 
                    . e($subcategoria->nombre) . 
                '</span>
            </div>
            <div class="table-cell categoria">
                <span class="table-cell-label">Categoría:</span>
                <span class="truncate-15 truncate-with-tooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . e(optional($subcategoria->categoria)->nombre ?? 'Sin categoría') . '">'
                    . e(optional($subcategoria->categoria)->nombre ?? 'Sin categoría') .
                '</span>
            </div>';
    };
@endphp
