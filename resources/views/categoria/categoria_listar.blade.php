@extends('base.listar')

@php
    $titulo = 'Listado de Categorías';

    $filtros = [
        ['name' => 'nombre', 'placeholder' => 'Buscar por nombre'],
        ['name' => 'descripcion', 'placeholder' => 'Buscar por descripción'],
    ];

    $rutaCrear = 'categorias.create';
    $rutaEditar = 'categorias.edit';
    
    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Descripción']
    ];
    
    $items = $categorias;
    
    $renderFila = function($categoria) {
        return '
            <div class="table-cell">
                <span class="table-cell-label">Id:</span>
                <span>' . e($categoria->id) . '</span>
            </div>
            <div class="table-cell nombre">
                <span class="table-cell-label">Nombre:</span>
                <span class="truncate-15 truncate-with-tooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . e($categoria->nombre) . '">' 
                    . e($categoria->nombre) . 
                '</span>
            </div>
            <div class="table-cell descripcion">
                <span class="table-cell-label">Descripción:</span>
                <span class="truncate-15 truncate-with-tooltip"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="' . e($categoria->descripcion) . '">' 
                    . e($categoria->descripcion) . 
                '</span>
            </div>';
    };
@endphp

