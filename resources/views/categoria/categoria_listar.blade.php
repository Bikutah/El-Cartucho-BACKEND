@extends('base.listar')

@php
    $titulo = 'Listado de Categorías';
    $rutaCrear = 'categorias.create';
    $rutaEditar = 'categorias.edit';
    $columnas = ['Id', 'Nombre', 'Descripción'];
    $items = $categorias;
    $renderFila = fn($categoria) => '
        <div class="col">' . e($categoria->id) . '</div>
        <div class="col">' . e($categoria->nombre) . '</div>
        <div class="col">' . e($categoria->descripcion) . '</div>
    ';
@endphp
