@extends('base.listar')

@php
    $titulo = 'Listado de Zonas de Envío';

    $filtros = [
        ['name' => 'nombre', 'placeholder' => 'Buscar por nombre'],
        ['name' => 'cp', 'placeholder' => 'Buscar por CP'],
        [
            'name' => 'activa',
            'placeholder' => 'Filtrar por estado',
            'type' => 'select',
            'options' => [
                '1' => 'Activa',
                '0' => 'Inactiva',
            ]
        ]
    ];

    $rutaCrear = 'zonas-envio.create';
    $rutaEditar = 'zonas-envio.edit';
    $rutaEliminar = 'zonas-envio.destroy';

    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Rango CP'],
        ['label' => 'Costo'],
        ['label' => 'Orden'],
        ['label' => 'Estado'],
    ];

    $items = $zonas;

    $renderFila = function ($zona) {
        $estadoBadge = $zona->activa
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activa</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactiva</span>';

        return '
            <div class="table-cell" data-label="Id">
                <span class="table-cell-label">Id:</span>
                <span>' . e($zona->id) . '</span>
            </div>
            <div class="table-cell" data-label="Nombre">
                <span class="table-cell-label">Nombre:</span>
                <span class="fw-bold">' . e($zona->nombre) . '</span>
            </div>
            <div class="table-cell" data-label="Rango CP">
                <span class="table-cell-label">Rango CP:</span>
                <span>' . e($zona->cp_desde) . ' - ' . e($zona->cp_hasta) . '</span>
            </div>
            <div class="table-cell" data-label="Costo">
                <span class="table-cell-label">Costo:</span>
                <span>$' . number_format($zona->costo, 2, ',', '.') . ' ARS</span>
            </div>
            <div class="table-cell" data-label="Orden">
                <span class="table-cell-label">Orden:</span>
                <span>' . e($zona->orden) . '</span>
            </div>
            <div class="table-cell" data-label="Estado">
                <span class="table-cell-label">Estado:</span>
                ' . $estadoBadge . '
            </div>';
    };
@endphp
