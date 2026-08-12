@extends('base.formulario')

@php
    $titulo = 'Crear Zona de Envío';
    $action = route('zonas-envio.store');
    $method = 'POST';
    $rutaVolver = route('zonas-envio.index');
    $textoBoton = 'Crear';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre de la Zona',
            'placeholder' => 'Ej: Chubut, Patagonia, Centro',
            'required' => true,
        ],
        [
            'name' => 'cp_desde',
            'label' => 'Código Postal Desde',
            'placeholder' => 'Ej: 9000',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'cp_hasta',
            'label' => 'Código Postal Hasta',
            'placeholder' => 'Ej: 9299',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'costo',
            'label' => 'Costo de Envío ($)',
            'placeholder' => 'Ej: 8000.00',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'orden',
            'label' => 'Orden de Prioridad (Menor = Mayor prioridad)',
            'placeholder' => 'Ej: 0',
            'type' => 'number',
            'value' => '0',
            'required' => true,
        ],
        [
            'name' => 'activa',
            'label' => 'Estado',
            'type' => 'select',
            'options' => [
                '1' => 'Activa',
                '0' => 'Inactiva',
            ],
            'value' => '1',
        ],
    ];
@endphp
