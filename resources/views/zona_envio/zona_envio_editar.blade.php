@extends('base.formulario')

@php
    $titulo = 'Editar Zona de Envío';
    $action = route('zonas-envio.update', $zona->id);
    $method = 'PUT';
    $rutaVolver = route('zonas-envio.index');
    $textoBoton = 'Guardar';
    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre de la Zona',
            'placeholder' => 'Nombre de la zona de envío',
            'value' => $zona->nombre,
            'required' => true,
        ],
        [
            'name' => 'cp_desde',
            'label' => 'Código Postal Desde',
            'placeholder' => 'CP inicial',
            'type' => 'number',
            'value' => $zona->cp_desde,
            'required' => true,
        ],
        [
            'name' => 'cp_hasta',
            'label' => 'Código Postal Hasta',
            'placeholder' => 'CP final',
            'type' => 'number',
            'value' => $zona->cp_hasta,
            'required' => true,
        ],
        [
            'name' => 'costo',
            'label' => 'Costo de Envío ($)',
            'placeholder' => 'Costo',
            'type' => 'number',
            'value' => $zona->costo,
            'required' => true,
        ],
        [
            'name' => 'orden',
            'label' => 'Orden de Prioridad (Menor = Mayor prioridad)',
            'placeholder' => 'Orden',
            'type' => 'number',
            'value' => $zona->orden,
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
            'value' => $zona->activa ? '1' : '0',
        ],
    ];
@endphp
