@extends('base.listar')
@php
    $titulo = 'Listado de Productos';
    $rutaCrear = 'productos.create';
    $rutaEditar = 'productos.edit';
    $columnas = ['Id','Nombre', 'Descripción','PrecioUnitario','Stock','Categoría','Imagen'];
    $items = $productos;
    $renderFila = function($producto) {
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
    };
@endphp
