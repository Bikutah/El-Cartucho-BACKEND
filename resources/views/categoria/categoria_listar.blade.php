@extends('base.listar')

@php
    $titulo = 'Listado de Categorías';
    $rutaCrear = 'categorias.create';
    $rutaEditar = 'categorias.edit';
    $rutaEliminar = 'categorias.destroy';
    $encabezados = ['Nombre', 'Descripción'];
    $items = $categorias;
    $itemTexto = function($categoria) {
        return '
            <div class="col">' . e($categoria->nombre) . '</div>
            <div class="col">' . e($categoria->descripcion) . '</div>
        ';
    };
@endphp
