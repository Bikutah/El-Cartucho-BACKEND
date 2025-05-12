@extends('base.formulario')

@php
    $titulo = 'Editar Producto';
    $action = route('productos.update', $producto);
    $method = 'PUT';
    $rutaVolver = route('productos.index');
    $textoBoton = 'Actualizar';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre del producto',
            'required' => true,
            'value' => $producto->nombre
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción del producto',
            'required' => true,
            'value' => $producto->descripcion,
            'type' => 'textarea',
        ],
        [
            'name' => 'precioUnitario',
            'label' => 'Precio Unitario',
            'placeholder' => 'Precio unitario del producto',
            'type' => 'number',
            'required' => true,
            'value' => $producto->precioUnitario
        ],
        [
            'name' => 'stock',
            'label' => 'Stock',
            'placeholder' => 'Cantidad de productos en stock',
            'type' => 'number',
            'required' => true,
            'value' => $producto->stock
        ],
        [
            'name' => 'imagen',
            'label' => 'Imagen',
            'placeholder' => 'URL de la imagen del producto',
            'type' => 'url',
            'required' => false,
            'value' => $producto->imagen
        ],
        [
            'name' => 'categoria_id',
            'label' => 'Categoría',
            'placeholder' => 'Seleccione una categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => true,
            'value' => $producto->categoria_id
        ]
    ];
@endphp
