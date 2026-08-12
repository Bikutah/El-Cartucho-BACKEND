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
            <div class="status-details">
                <h3 class="status-title">{{ $pedido->estado_visible }}</h3>
                <p class="status-description">
                    Pago: <strong>{{ ucfirst($pedido->estado_pago) }}</strong> | Envío: <strong>{{ $pedido->estado_envio ? ucfirst(str_replace('_', ' ', $pedido->estado_envio)) : 'Sin asignar' }}</strong>
                </p>
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
            @if($pedido->user)
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value">
                        <a href="{{ route('clientes.show', $pedido->user->id) }}" class="text-decoration-none fw-bold text-primary">
                            <i class="fas fa-user me-1"></i>{{ $pedido->user->name }} {{ $pedido->user->apellido }}
                        </a>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email de contacto:</span>
                    <span class="info-value">{{ $pedido->email ?? $pedido->user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Domicilio de envío:</span>
                    <span class="info-value">{{ $pedido->domicilio ?? $pedido->user->domicilio ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ciudad / CP:</span>
                    <span class="info-value">{{ $pedido->ciudad ?? $pedido->user->ciudad ?? '-' }} (CP {{ $pedido->codigo_postal ?? $pedido->user->codigo_postal ?? '-' }})</span>
                </div>
                <div class="info-row">
                    <span class="info-label">UID de Firebase:</span>
                    <span class="info-value customer-uid">{{ $pedido->firebase_uid }}</span>
                </div>
            @else
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">
                        <span class="status-badge status-unknown"><i class="fas fa-user-slash me-1"></i>Sin cliente asociado</span>
                    </span>
                </div>
                @if($pedido->email || $pedido->domicilio)
                <div class="info-row">
                    <span class="info-label">Envío:</span>
                    <span class="info-value">{{ $pedido->domicilio }}, {{ $pedido->ciudad }} (CP {{ $pedido->codigo_postal }})</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">UID de Firebase:</span>
                    <span class="info-value customer-uid">{{ $pedido->firebase_uid }}</span>
                </div>
            @endif
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
                    <small class="text-muted d-block fw-normal">(Referencia externa MP)</small>
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
            <div class="info-row">
                <span class="info-label">Preferencia MP:</span>
                <span class="info-value text-break">
                    @if($pedido->mercado_pago_preference_id)
                        <span class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                            <span class="mp-id text-break">{{ $pedido->mercado_pago_preference_id }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary p-1 border-0" aria-label="Copiar Preference ID" onclick="copyToClipboard('{{ $pedido->mercado_pago_preference_id }}', this)" title="Copiar Preference ID">
                                <i class="far fa-copy"></i>
                            </button>
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment ID MP:</span>
                <span class="info-value text-break">
                    @if($pedido->mercado_pago_id)
                        <span class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                            <span class="mp-id text-break">{{ $pedido->mercado_pago_id }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary p-1 border-0" aria-label="Copiar Payment ID" onclick="copyToClipboard('{{ $pedido->mercado_pago_id }}', this)" title="Copiar Payment ID">
                                <i class="far fa-copy"></i>
                            </button>
                        </span>
                    @else
                        <span class="text-muted">Sin pago registrado</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-icon payment">
                <i class="fas fa-credit-card"></i>
            </div>
            <h4 class="detail-title">Información de Pago y Envío</h4>
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
                    <span class="badge {{ $pedido->estado_pago === 'pagado' ? 'bg-success' : ($pedido->estado_pago === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ ucfirst($pedido->estado_pago) }}
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado de Envío:</span>
                <span class="info-value">
                    @if($pedido->estado_envio)
                        <span class="badge bg-primary">
                            {{ ucfirst(str_replace('_', ' ', $pedido->estado_envio)) }}
                        </span>
                    @else
                        <span class="badge bg-secondary">Sin asignar</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Transportista:</span>
                <span class="info-value">{{ $pedido->transportista ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tracking N°:</span>
                <span class="info-value">{{ $pedido->tracking_numero ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Gestión de Envío Admin (solo si el pedido está pagado) --}}
@if($pedido->estado_pago === 'pagado')
<div class="card shadow border-0 mb-4 mt-3">
    <div class="card-header bg-white py-3 fw-bold">
        <i class="fas fa-truck me-2"></i>Gestión de Envío
    </div>
    <div class="card-body">
        <form action="{{ route('pedidos.update', $pedido->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="estado_envio" class="form-label fw-semibold">Estado de Envío</label>
                    <select name="estado_envio" id="estado_envio" class="form-select">
                        @php
                            $actualEnvio = $pedido->estado_envio ?? 'sin_preparar';
                            $opcionesValidas = [];
                            if ($actualEnvio === 'sin_preparar') {
                                $opcionesValidas = ['sin_preparar' => 'Sin preparar (Actual)', 'preparando' => 'Preparando'];
                            } elseif ($actualEnvio === 'preparando') {
                                $opcionesValidas = ['preparando' => 'Preparando (Actual)', 'enviado' => 'Enviado', 'sin_preparar' => 'Sin preparar (Retroceso)'];
                            } elseif ($actualEnvio === 'enviado') {
                                $opcionesValidas = ['enviado' => 'Enviado (Actual)', 'entregado' => 'Entregado', 'devuelto' => 'Devuelto', 'preparando' => 'Preparando (Retroceso)'];
                            } elseif ($actualEnvio === 'entregado') {
                                $opcionesValidas = ['entregado' => 'Entregado (Actual)', 'devuelto' => 'Devuelto', 'enviado' => 'Enviado (Retroceso)'];
                            } elseif ($actualEnvio === 'devuelto') {
                                $opcionesValidas = ['devuelto' => 'Devuelto (Terminal)'];
                            }
                        @endphp
                        @foreach($opcionesValidas as $val => $text)
                            <option value="{{ $val }}" {{ $val === $actualEnvio ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="transportista" class="form-label fw-semibold">Transportista (Opcional)</label>
                    <input type="text" name="transportista" id="transportista" class="form-control" value="{{ old('transportista', $pedido->transportista) }}" placeholder="Ej: Andreani, OCA">
                </div>
                <div class="col-md-4">
                    <label for="tracking_numero" class="form-label fw-semibold">Número de Tracking (Opcional)</label>
                    <input type="text" name="tracking_numero" id="tracking_numero" class="form-control" value="{{ old('tracking_numero', $pedido->tracking_numero) }}" placeholder="Ej: TRK123456789">
                </div>
                <div class="col-12">
                    <label for="observacion" class="form-label fw-semibold">Observación <small class="text-muted">(Obligatoria si se retrocede un estado)</small></label>
                    <textarea name="observacion" id="observacion" class="form-control" rows="2" placeholder="Ingrese una observación si aplica..."></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Actualizar Envío
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Historial Completo de Estados -->
<div class="card shadow border-0 mb-4 mt-4">
    <div class="card-header bg-white py-3 fw-bold">
        <i class="fas fa-history me-2"></i>Historial Completo de Estados
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Transición</th>
                        <th>Usuario</th>
                        <th>Origen</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedido->historialEstados as $historial)
                        <tr>
                            <td>{{ $historial->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $historial->tipo === 'pago' ? 'bg-info text-dark' : 'bg-primary' }}">
                                    {{ ucfirst($historial->tipo) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $historial->estado_anterior ?? '—' }}</span>
                                <i class="fas fa-arrow-right mx-1 text-secondary"></i>
                                <span class="fw-bold">{{ $historial->estado_nuevo }}</span>
                            </td>
                            <td>{{ optional($historial->user)->name ?? 'Sistema' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $historial->origen }}</span></td>
                            <td>{{ $historial->observacion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No hay historial registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                <span class="summary-label">Envío {{ $pedido->zonaEnvio ? '(' . $pedido->zonaEnvio->nombre . ')' : '' }}:</span>
                <span class="summary-value">
                    @if($pedido->costo_envio > 0)
                        ${{ number_format($pedido->costo_envio, 2, ',', '.') }}
                    @else
                        Sin registro de envío
                    @endif
                </span>
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

@push('scripts')
<script>
function copyToClipboard(text, button) {
    if (!text) return;
    const icon = button ? button.querySelector('i') : null;
    const originalClass = icon ? icon.className : '';

    function showSuccess() {
        if (icon) {
            icon.className = 'fas fa-check text-success';
            setTimeout(() => {
                icon.className = originalClass;
            }, 1500);
        }
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showSuccess).catch(() => {
            fallbackCopy(text);
            showSuccess();
        });
    } else {
        fallbackCopy(text);
        showSuccess();
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (err) {}
    document.body.removeChild(textArea);
}
</script>
@endpush