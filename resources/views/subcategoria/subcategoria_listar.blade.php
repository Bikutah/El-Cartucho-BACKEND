@extends('base.listar')

@php
    $titulo = 'Listado de Subcategorias';
    $rutaCrear = 'subcategorias.create';
    $rutaEditar = 'subcategorias.edit';
    $rutaEliminar = 'subcategorias.destroy';
    $encabezados = ['Id','Nombre','Categoria'];
    $items = $subcategorias;
    $itemTexto = function($subcategoria) {
        return '
            <div class="col">' . e($subcategoria->id) . '</div>
            <div class="col">' . e($subcategoria->nombre) . '</div>
            <div class="col">' . e(optional($subcategoria->categoria)->nombre ?? 'Sin categoría') . '</div>
        ';
    };
@endphp
