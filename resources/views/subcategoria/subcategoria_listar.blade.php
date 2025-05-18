@extends('base.listar')

@php
    $titulo = 'Listado de Subcategorías';
    $rutaCrear = 'subcategorias.create';
    $rutaEditar = 'subcategorias.edit';
    $columnas = ['Id','Nombre','Categoría'];
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

    $renderFila = fn($subcategoria) => '
        <div class="col">' . e($subcategoria->id) . '</div>
        <div class="col">' . e($subcategoria->nombre) . '</div>
        <div class="col">' . e(optional($subcategoria->categoria)->nombre ?? 'Sin categoría') . '</div>   
    ';
@endphp
