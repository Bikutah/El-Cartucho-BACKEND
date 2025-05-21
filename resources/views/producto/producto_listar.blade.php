@extends('base.listar')
@php
    $titulo = 'Listado de Productos';

    $filtros = [
        ['name' => 'nombre', 'placeholder' => 'Buscar por nombre'],
        ['name' => 'stock', 'placeholder' => 'Stock'],
        [
            'name' => 'categoria',
            'placeholder' => 'Filtrar por categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'nombre')->toArray()
        ]
    ];

    $rutaCrear = 'productos.create';
    $rutaEditar = 'productos.edit';
    $columnas = ['Id','Nombre', 'Descripción','Precio','Stock','Categoría','Imagenes'];
    $items = $productos;
    $renderFila = function($producto) {
        $html = '
            <div class="col">' . e($producto->id) . '</div>
            <div class="col">' . e($producto->nombre) . '</div>
            <div class="col">' . e($producto->descripcion) . '</div>
            <div class="col">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
            <div class="col">' . e($producto->stock) . '</div>
            <div class="col">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
            <div class="col">
                <a href="' . route('productos.imagenes', $producto) . '" class="btn btn-primary"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver imágenes">
                    <i class="fas fa-images"></i>
                    <span class="badge bg-light text-dark">' . count($producto->imagenes) . '</span>
                </a>
            </div>';
        return $html;
    };
@endphp