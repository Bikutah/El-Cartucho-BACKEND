@extends('base.formulario')

@php
    $titulo = 'Editar Categoría';
    $action = route('categorias.update', $categoria);
    $method = 'PUT';
    $rutaVolver = route('categorias.index');
    $textoBoton = 'Actualizar';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre de la categoría',
            'value' => $categoria->nombre,
            'required' => true,
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción de la categoría',
            'value' => $categoria->descripcion,
            'required' => true,
            'type' => 'textarea',
        ],
    ];
@endphp
