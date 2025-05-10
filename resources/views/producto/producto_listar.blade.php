@extends('base.listar')

@php
    $titulo = 'Listado de Productos';
    $rutaCrear = 'productos.create';
    $rutaEditar = 'productos.edit';
    $rutaEliminar = 'productos.destroy';
    $encabezados = ['Id','Nombre', 'Descripción','PrecioUnitario','Stock','Categoría'];
    $items = $productos;
    $itemTexto = function($producto) {
        $html = '
            <div class="col">' . e($producto->id) . '</div>
            <div class="col">' . e($producto->nombre) . '</div>
            <div class="col">' . e($producto->descripcion) . '</div>
            <div class="col">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
            <div class="col">' . e($producto->stock) . '</div>
            <div class="col">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
        ';

        // Mostrar imagen si existe
        if (!empty($producto->image_url)) {
            $html .= '<div class="col"><img src="' . e($producto->image_url) . '" alt="Imagen" style="max-width: 80px; max-height: 80px;"></div>';
        } else {
            $html .= '<div class="col">Sin imagen</div>';
        }

        return $html;
    };
@endphp
