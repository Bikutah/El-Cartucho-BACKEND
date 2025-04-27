@extends('base.listar')

@php
    $titulo = 'Listado de Productos';
    $rutaCrear = 'productos.create';
    $rutaEditar = 'productos.edit';
    $rutaEliminar = 'productos.destroy';
    $encabezados = ['Id','Nombre', 'Descripción','PrecioUnitario','Stock','Categoría'];
    $items = $productos;
    $itemTexto = function($producto) {
        return '
            <div class="col">' . e($producto->id) . '</div>
            <div class="col">' . e($producto->nombre) . '</div>
            <div class="col">' . e($producto->descripcion) . '</div>
            <div class="col">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
            <div class="col">' . e($producto->stock) . '</div>
            <div class="col">' . e($producto->categoria->nombre) . '</div>
        ';
    };
@endphp
