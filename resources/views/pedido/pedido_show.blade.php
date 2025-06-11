@extends('layouts.app')
@section('title', 'Detalle del Pedido #' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT))

@section('content')

<!-- Header Section -->
<div class="header-section">
    <div class="header-content">
        <div class="header-title-wrapper">
            <h1 class="header-title">
                <i class="fas fa-receipt header-icon"></i>
                <span class="product-name">Pedido</span>
                <span class="breadcrumb-separator">/</span>
                <span class="section-name">#{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="{{ route('pedidos.index') }}" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span class="btn-text">Volver al listado</span>
            </a>
        </div>
    </div>
</div>

<!-- Order Status Card -->
<div class="order-status-card">
    <div class="status-header">
        <div class="status-info">
            @php
            $statusConfig = [
            'pendiente' => [
            'icon' => 'fas fa-clock',
            'class' => 'status-pending',
            'title' => 'Pedido Pendiente',
            'description' => 'El pedido está esperando confirmación de pago'
            ],
            'pagado' => [
            'icon' => 'fas fa-check-circle',
            'class' => 'status-paid',
            'title' => 'Pedido Pagado',
            'description' => 'El pago ha sido confirmado exitosamente'
            ],
            'cancelado' => [
            'icon' => 'fas fa-times-circle',
            'class' => 'status-cancelled',
            'title' => 'Pedido Cancelado',
            'description' => 'El pedido ha sido cancelado'
            ]
            ];
            $config = $statusConfig[$pedido->estado] ?? $statusConfig['pendiente'];
            @endphp

            <div class="status-icon {{ $config['class'] }}">
                <i class="{{ $config['icon'] }}"></i>
            </div>
            <div class="status-details">
                <h3 class="status-title">{{ $config['title'] }}</h3>
                <p class="status-description">{{ $config['description'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Grid -->
<div class="order-details-grid">
    <!-- Customer Information -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon customer">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="detail-title">Información del Cliente</h4>
        </div>
        <div class="detail-content">
            <div class="info-row">
                <span class="info-label">ID de Usuario:</span>
                <span class="info-value customer-uid">{{ $pedido->firebase_uid }}</span>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon order">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h4 class="detail-title">Información del Pedido</h4>
        </div>
        <div class="detail-content">
            <div class="info-row">
                <span class="info-label">Número de Pedido:</span>
                <span class="info-value">
                    <span class="order-number">#{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Creación:</span>
                <span class="info-value">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Última Actualización:</span>
                <span class="info-value">{{ $pedido->updated_at->format('d/m/Y H:i') }}</span>
            </div>
            @if($pedido->mercado_pago_id)
            <div class="info-row">
                <span class="info-label">ID MercadoPago:</span>
                <span class="info-value">
                    <span class="mp-id">{{ $pedido->mercado_pago_id }}</span>
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Payment Information -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon payment">
                <i class="fas fa-credit-card"></i>
            </div>
            <h4 class="detail-title">Información de Pago</h4>
        </div>
        <div class="detail-content">
            <div class="info-row">
                <span class="info-label">Total del Pedido:</span>
                <span class="info-value">
                    <span class="total-amount">${{ number_format($pedido->total, 2, ',', '.') }}</span>
                    <small class="currency">ARS</small>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado de Pago:</span>
                <span class="info-value">
                    <span class="payment-status {{ $pedido->estado }}">
                        @if($pedido->estado === 'pagado')
                        <i class="fas fa-check-circle"></i> Pagado
                        @elseif($pedido->estado === 'pendiente')
                        <i class="fas fa-clock"></i> Pendiente
                        @else
                        <i class="fas fa-times-circle"></i> Cancelado
                        @endif
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Método de Pago:</span>
                <span class="info-value">
                    @if($pedido->mercado_pago_id)
                    <span class="payment-method">
                        <i class="fab fa-cc-mastercard"></i>
                        MercadoPago
                    </span>
                    @else
                    <span class="payment-method pending">
                        <i class="fas fa-question-circle"></i>
                        No definido
                    </span>
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Order Items -->
<div class="order-items-section">
    <div class="section-header">
        <h3 class="section-title">
            <i class="fas fa-list"></i>
            Productos del Pedido
        </h3>
        <div class="section-meta">
            <span class="items-count">{{ $pedido->detalles->count() }} productos</span>
        </div>
    </div>

    <div class="items-container">
        @forelse($pedido->detalles as $detalle)
        <div class="item-card">
            <div class="item-image">
                @if(optional($detalle->producto)->imagenes->first())
                <img src="{{ $detalle->producto->imagenes->first()->imagen_url }}"
                    alt="{{ optional($detalle->producto)->nombre }}"
                    class="product-image">
                @else
                <div class="product-placeholder">
                    <i class="fas fa-image"></i>
                </div>
                @endif
            </div>

            <div class="item-details">
                <h5 class="item-name">
                    {{ optional($detalle->producto)->nombre ?? 'Producto eliminado' }}
                </h5>
                @if(optional($detalle->producto)->descripcion)
                <p class="item-description">
                    {{ Str::limit($detalle->producto->descripcion, 100) }}
                </p>
                @endif
                <div class="item-meta">
                    <span class="item-sku">Id: {{ optional($detalle->producto)->id ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="item-quantity">
                <span class="quantity-label">Cantidad</span>
                <span class="quantity-value">{{ $detalle->cantidad }}</span>
            </div>

            <div class="item-pricing">
                <div class="unit-price">
                    <span class="price-label">Precio unitario</span>
                    <span class="price-value">${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</span>
                </div>
                <div class="total-price">
                    <span class="price-label">Subtotal</span>
                    <span class="price-value total">${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <h4 class="empty-title">No hay productos</h4>
            <p class="empty-description">Este pedido no tiene productos asociados.</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Order Summary -->
<div class="order-summary-section">
    <div class="summary-card">
        <h4 class="summary-title">Resumen del Pedido</h4>
        <div class="summary-content">
            @php
            $subtotal = $pedido->detalles->sum(function($detalle) {
            return $detalle->precio_unitario * $detalle->cantidad;
            });
            $totalItems = $pedido->detalles->sum('cantidad');
            @endphp

            <div class="summary-row">
                <span class="summary-label">Subtotal ({{ $totalItems }} productos):</span>
                <span class="summary-value">${{ number_format($subtotal, 2, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Envío:</span>
                <span class="summary-value">Gratis</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Impuestos:</span>
                <span class="summary-value">Incluidos</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row total">
                <span class="summary-label">Total:</span>
                <span class="summary-value">${{ number_format($pedido->total, 2, ',', '.') }} ARS</span>
            </div>
        </div>
    </div>
</div>
@endsection