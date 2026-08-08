@extends('layouts.app')
@section('title', 'Detalle del Cliente: ' . $cliente->name . ($cliente->apellido ? ' ' . $cliente->apellido : ''))

@section('content')

<!-- Header Section -->
<div class="header-section">
    <div class="header-content">
        <div class="header-title-wrapper">
            <h1 class="header-title">
                <i class="fas fa-users header-icon"></i>
                <span class="product-name">Cliente</span>
                <span class="breadcrumb-separator">/</span>
                <span class="section-name">{{ $cliente->name }} {{ $cliente->apellido }}</span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="{{ session('listado_url.clientes', route('clientes.index')) }}" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span class="btn-text">Volver al listado</span>
            </a>
        </div>
    </div>
</div>

<!-- Order Details Grid (reutilizando estilos de pedido_show) -->
<div class="order-details-grid mb-4">
    <!-- Información Personal -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon customer">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="detail-title">Datos Personales</h4>
        </div>
        <div class="detail-content">
            <div class="info-row">
                <span class="info-label">Nombre y Apellido:</span>
                <span class="info-value">{{ $cliente->name }} {{ $cliente->apellido }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $cliente->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Domicilio:</span>
                <span class="info-value">{{ $cliente->domicilio ?? 'No especificado' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ciudad:</span>
                <span class="info-value">{{ $cliente->ciudad ?? 'No especificada' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Código Postal:</span>
                <span class="info-value">{{ $cliente->codigo_postal ?? 'No especificado' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Registro:</span>
                <span class="info-value">{{ $cliente->created_at ? $cliente->created_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Estadísticas de Compras -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon order">
                <i class="fas fa-chart-line"></i>
            </div>
            <h4 class="detail-title">Estadísticas de Compras</h4>
        </div>
        <div class="detail-content">
            <div class="info-row">
                <span class="info-label">Pedidos Realizados:</span>
                <span class="info-value">
                    <span class="badge bg-secondary rounded-pill fs-6">{{ $cantidadPedidos }}</span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Gastado:</span>
                <span class="info-value text-success fw-bold">${{ number_format($totalGastado, 2, ',', '.') }} ARS</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ticket Promedio:</span>
                <span class="info-value">${{ number_format($ticketPromedio, 2, ',', '.') }} ARS</span>
            </div>
            <div class="info-row">
                <span class="info-label">Primer Pedido:</span>
                <span class="info-value">{{ $primerPedido ? $primerPedido->created_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Último Pedido:</span>
                <span class="info-value">{{ $ultimoPedido ? $ultimoPedido->created_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabla Historial de Pedidos -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-history me-2"></i>Historial de Pedidos
        </h5>
    </div>
    <div class="card-body p-0">
        @if($pedidos->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                Este cliente no tiene pedidos registrados.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Pedido</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidos as $pedido)
                            @php
                                $statusBadges = [
                                    'pendiente' => '<span class="status-badge status-pending"><i class="fas fa-clock"></i> Pendiente</span>',
                                    'pagado'    => '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Pagado</span>',
                                    'cancelado' => '<span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> Cancelado</span>',
                                ];
                                $badge = $statusBadges[$pedido->estado] ?? '<span class="status-badge status-unknown">' . e($pedido->estado) . '</span>';
                            @endphp
                            <tr>
                                <td data-label="ID Pedido">
                                    <span class="order-number">#{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td data-label="Fecha">
                                    {{ $pedido->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td data-label="Total">
                                    <span class="fw-bold text-success">${{ number_format($pedido->total, 2, ',', '.') }}</span>
                                </td>
                                <td data-label="Estado">
                                    {!! $badge !!}
                                </td>
                                <td data-label="Acciones">
                                    <a href="{{ route('pedidos.show', $pedido->id) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver pedido">
                                        <i class="fas fa-eye me-1"></i>Ver pedido
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
