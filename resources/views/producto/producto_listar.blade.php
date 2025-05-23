@extends('base.listar')

@php
    use Illuminate\Support\Str;
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

    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Descripción'],
        ['label' => 'Precio'],
        ['label' => 'Stock'],
        ['label' => 'Categoría'],
        ['label' => 'Imágenes']
    ];

    $items = $productos;

    $renderFila = function($producto) {
        return '
            <div class="table-cell">
                <span class="table-cell-label">Id:</span>
                <span>' . e($producto->id) . '</span>
            </div>
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
            <div class="table-cell">
                <span class="table-cell-label">Precio:</span>
                <span>$' . number_format($producto->precioUnitario, 2, ',', '.') . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Stock:</span>
                <span>' . e($producto->stock) . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Categoría:</span>
                <span>' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Imágenes:</span>
                <span>
                    <a href="' . route('productos.imagenes', $producto) . '" class="action-btn"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Ver imágenes">
                        <i class="fas fa-images"></i>
                        <span class="badge bg-light text-dark">' . count($producto->imagenes) . '</span>
                    </a>
                </span>
            </div>';
    };
@endphp

