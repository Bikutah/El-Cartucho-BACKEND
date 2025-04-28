@extends('base.formulario')

@php
    $titulo = 'Crear Categoría';
    $action = route('categorias.store');
    $method = 'POST';
    $rutaVolver = route('categorias.index');
    $textoBoton = 'Crear';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre de la categoría',
            'required' => true,
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción de la categoría',
            'required' => true,
            'type' => 'textarea',
        ],
    ];
@endphp
