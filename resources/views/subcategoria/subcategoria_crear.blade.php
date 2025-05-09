@extends('base.formulario')

@php
    $titulo = 'Crear Subcategoría';
    $action = route('subcategorias.store');
    $method = 'POST';
    $rutaVolver = route('subcategorias.index');
    $textoBoton = 'Crear';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre de la subcategoría',
            'required' => true,
        ],
        [
            'name' => 'categoria_id',
            'label' => 'Categoría',
            'placeholder' => 'Seleccione una categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => true,
        ]
    ];
@endphp
