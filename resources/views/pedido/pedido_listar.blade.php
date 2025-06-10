@extends('base.listar')

@php
    use Illuminate\Support\Str;
    $titulo = 'Gestión de Pedidos';

    $filtros = [
        ['name' => 'id', 'placeholder' => 'Buscar por ID del pedido'],
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
        ],
        ['name' => 'total_min', 'placeholder' => 'Total mínimo ($)'],
        ['name' => 'total_max', 'placeholder' => 'Total máximo ($)']
    ];

    $columnas = [
        ['label' => 'ID'],
        ['label' => 'Cliente'],
        ['label' => 'Estado'],
        ['label' => 'Total'],
        ['label' => 'Fecha'],
        ['label' => 'Productos'],
    ];

    $items = $pedidos;
    $rutaVer = 'pedidos.show'; 
    $rutaImprimir = 'pedidos.imprimir';
    $renderFila = function($pedido) {
        // Función para obtener el badge del estado
        $getEstadoBadge = function($estado) {
            $badges = [
                'pendiente' => '<span class="status-badge status-pending">
                    <i class="fas fa-clock"></i>
                    <span>Pendiente</span>
                </span>',
                'pagado' => '<span class="status-badge status-paid">
                    <i class="fas fa-check-circle"></i>
                    <span>Pagado</span>
                </span>',
                'cancelado' => '<span class="status-badge status-cancelled">
                    <i class="fas fa-times-circle"></i>
                    <span>Cancelado</span>
                </span>'
            ];
            return $badges[$estado] ?? '<span class="status-badge status-unknown">Desconocido</span>';
        };

        // Resumen de productos
        $productosResumen = collect($pedido->detalles)->map(function ($detalle) {
            $nombreProducto = optional($detalle->producto)->nombre ?? 'Producto eliminado';
            return [
                'nombre' => $nombreProducto,
                'cantidad' => $detalle->cantidad,
                'precio' => $detalle->precio_unitario
            ];
        });

        $totalProductos = $productosResumen->sum('cantidad');
        $primerProducto = $productosResumen->first();

        return '
            <div class="table-cell" data-label="ID">
                <span class="table-cell-label">ID:</span>
                <div class="order-id">
                    <span class="order-number">#' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT) . '</span>
                </div>
            </div>
            <div class="table-cell" data-label="Cliente">
                <span class="table-cell-label">Cliente:</span>
                <div class="customer-info">
                    <div class="customer-details">
                        <span class="customer-uid truncate-15" 
                              data-bs-toggle="tooltip" 
                              data-bs-placement="top" 
                              title="' . e($pedido->firebase_uid) . '">
                            ' . e(Str::limit($pedido->firebase_uid, 12)) . '
                        </span>
                    </div>
                </div>
            </div>
            <div class="table-cell" data-label="Estado">
                <span class="table-cell-label">Estado:</span>
                ' . $getEstadoBadge($pedido->estado) . '
            </div>
            <div class="table-cell" data-label="Total">
                <span class="table-cell-label">Total:</span>
                <div class="order-total">
                    <span class="amount">$' . number_format($pedido->total, 2, ',', '.') . '</span>
                    <small class="currency">ARS</small>
                </div>
            </div>
            <div class="table-cell" data-label="Fecha">
                <span class="table-cell-label">Fecha:</span>
                <div class="order-date">
                    <span class="date">' . $pedido->created_at->format('d/m/Y') . '</span>
                    <small class="time">' . $pedido->created_at->format('H:i') . '</small>
                </div>
            </div>
            <div class="table-cell" data-label="Productos">
                <span class="table-cell-label">Productos:</span>
                <div class="products-summary">
                    ' . ($primerProducto ? '
                        <div class="product-item">
                            <span class="product-name">' . e(Str::limit($primerProducto['nombre'], 20)) . '</span>
                            <span class="product-quantity">x' . $primerProducto['cantidad'] . '</span>
                        </div>
                    ' : '<span class="no-products">Sin productos</span>') . '
                    ' . ($totalProductos > 1 ? '
                        <div class="more-products">
                            <span class="more-count">+' . ($totalProductos - 1) . ' más</span>
                        </div>
                    ' : '') . '
                </div>
            </div>';
    };
@endphp