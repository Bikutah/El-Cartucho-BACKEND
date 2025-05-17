@extends('base.listar')

@php
    $titulo = 'Listado de Subcategorias';
    $rutaCrear = 'subcategorias.create';
    $rutaEditar = 'subcategorias.edit';
    $columnas = ['Id','Nombre','Categoría'];
    $items = $subcategorias;
    $renderFila = fn($subcategoria) => '
        <div class="col">' . e($subcategoria->id) . '</div>
        <div class="col">' . e($subcategoria->nombre) . '</div>
        <div class="col">' . e(optional($subcategoria->categoria)->nombre ?? 'Sin categoría') . '</div>   
    ';
@endphp
