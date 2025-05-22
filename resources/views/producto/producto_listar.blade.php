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

    // 🔧 Versión con columnas responsive
    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Descripción', 'class' => 'd-none d-md-block'],
        ['label' => 'Precio', 'class' => 'd-none d-md-block'],
        ['label' => 'Stock'],
        ['label' => 'Categoría'],
        ['label' => 'Imágenes']
    ];

    $items = $productos;

    // 🔧 También el renderFila con clases
    $renderFila = function($producto) {
        return '
            <div class="col">' . e($producto->id) . '</div>
            <div class="col" title="' . e($producto->nombre) . '">' . e(Str::limit($producto->nombre, 15)) . '</div>
            <div class="col d-none d-md-block">' . e($producto->descripcion) . '</div>
            <div class="col d-none d-md-block">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
            <div class="col">' . e($producto->stock) . '</div>
            <div class="col">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
            <div class="col">
                <a href="' . route('productos.imagenes', $producto) . '" class="btn btn-primary"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Ver imágenes">
                    <i class="fas fa-images"></i>
                    <span class="badge bg-light text-dark">' . count($producto->imagenes) . '</span>
                </a>
            </div>';
    };

@endphp
