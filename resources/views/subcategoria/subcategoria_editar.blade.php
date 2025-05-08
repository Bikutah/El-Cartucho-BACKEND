@extends('base.formulario')

@php
    $titulo = 'Editar Subcategoría';
    $action = route('subcategorias.update', $subcategoria);
    $method = 'PUT';
    $rutaVolver = route('subcategorias.index');
    $textoBoton = 'Actualizar';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre de la subcategoría',
            'required' => true,
            'value' => $subcategoria->nombre
        ],
        [
            'name' => 'categoria_id',
            'label' => 'Categoría',
            'placeholder' => 'Seleccione una categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => true,
            'value' => $subcategoria->categoria_id
        ]
    ];
@endphp
