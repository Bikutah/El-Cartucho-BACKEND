@extends('base.listar')

@php
    use Illuminate\Support\Str;
    $titulo = 'Listado de Pedidos';

    $filtros = [
        ['name' => 'id', 'placeholder' => 'Buscar por ID'],
        ['name' => 'firebase_uid', 'placeholder' => 'UID de Usuario'],
        [
            'name' => 'estado',
            'placeholder' => 'Filtrar por estado',
            'type' => 'select',
            'options' => [
                'pendiente' => 'Pendiente',
                'pagado' => 'Pagado',
                'cancelado' => 'Cancelado'
            ]
        ]
    ];

    $columnas = [
        ['label' => 'ID'],
        ['label' => 'UID'],
        ['label' => 'Estado'],
        ['label' => 'Total'],
        ['label' => 'Fecha'],
        ['label' => 'Productos']
    ];

    $items = $pedidos;

    $renderFila = function($pedido) {
        $productosHtml = collect($pedido->detalles)->map(function ($detalle) {
            return '<li>' . e(optional($detalle->producto)->nombre ?? 'Producto eliminado') .
                ' x' . $detalle->cantidad .
                ' ($' . number_format($detalle->precio_unitario, 2, ',', '.') . ')</li>';
        })->implode('');

        return '
            <div class="table-cell">
                <span class="table-cell-label">ID:</span>
                <span>' . $pedido->id . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">UID:</span>
                <span class="truncate-15 truncate-with-tooltip"
                      data-bs-toggle="tooltip"
                      data-bs-placement="top"
                      title="' . e($pedido->firebase_uid) . '">' . e($pedido->firebase_uid) . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Estado:</span>
                <span>' . ucfirst($pedido->estado) . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Total:</span>
                <span>$' . number_format($pedido->total, 2, ',', '.') . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Fecha:</span>
                <span>' . $pedido->created_at->format('d/m/Y H:i') . '</span>
            </div>
            <div class="table-cell">
                <span class="table-cell-label">Productos:</span>
                <ul class="mb-0">' . $productosHtml . '</ul>
            </div>';
    };
@endphp
