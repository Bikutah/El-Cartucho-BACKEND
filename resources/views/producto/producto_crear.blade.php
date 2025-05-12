@extends('base.formulario')

@php
    $titulo = 'Crear Producto';
    $action = route('productos.store');
    $method = 'POST';
    $rutaVolver = route('productos.index');
    $textoBoton = 'Crear';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre del producto',
            'required' => true,
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción del producto',
            'required' => true,
            'type' => 'textarea',
            'rows' => 4,
            'cols' => 50,
        ],
        [
            'name' => 'precioUnitario',
            'label' => 'Precio Unitario',
            'placeholder' => 'Precio unitario del producto',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'stock',
            'label' => 'Stock',
            'placeholder' => 'Cantidad de productos en stock',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'imagenes[]', // importante para subir múltiples archivos
            'label' => 'Imágenes del producto',
            'type' => 'file',
            'required' => true,
            'multiple' => true, // personalización para el formulario
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

