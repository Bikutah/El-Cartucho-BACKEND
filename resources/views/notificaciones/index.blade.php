@extends('layouts.app')
@section('title', 'Notificaciones Push — El Cartucho')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Notificaciones Push</h1>
        <p class="text-muted small mb-0">Enviá alertas a los usuarios con PWA instalada</p>
    </div>
    <div class="badge fs-6 px-3 py-2" style="background-color: var(--color-indigo); color: white;">
        <i class="fas fa-mobile-alt me-2"></i>
        {{ $totalSuscriptos }} {{ $totalSuscriptos === 1 ? 'usuario suscripto' : 'usuarios suscriptos' }}
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- Formulario de envío --}}
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, var(--color-indigo) 0%, #1b194f 100%); color: white; border-radius: 12px 12px 0 0;">
                <i class="fas fa-paper-plane"></i>
                <h6 class="m-0 fw-semibold">Nueva Notificación</h6>
            </div>
            <div class="card-body p-4">
                @if($totalSuscriptos === 0)
                    <div class="text-center py-4">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay usuarios suscriptos a notificaciones todavía.</p>
                        <p class="text-muted small">Los usuarios pueden activarlas desde su perfil en la app.</p>
                    </div>
                @else
                    <form action="{{ route('notificaciones.enviar') }}" method="POST" id="form-notificacion">
                        @csrf

                        <div class="mb-3">
                            <label for="notif_titulo" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('titulo') is-invalid @enderror"
                                id="notif_titulo"
                                name="titulo"
                                maxlength="100"
                                placeholder="ej: ¡Nuevo producto disponible!"
                                value="{{ old('titulo') }}"
                                required
                            >
                            <div class="form-text text-end" id="titulo-count">0/100</div>
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notif_mensaje" class="form-label fw-semibold">Mensaje <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control @error('mensaje') is-invalid @enderror"
                                id="notif_mensaje"
                                name="mensaje"
                                rows="3"
                                maxlength="300"
                                placeholder="ej: Tenemos nuevos juegos de SNES en stock. ¡Entrá a verlos!"
                                required
                            >{{ old('mensaje') }}</textarea>
                            <div class="form-text text-end" id="mensaje-count">0/300</div>
                            @error('mensaje')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="notif_url" class="form-label fw-semibold">URL al hacer click <span class="text-muted fw-normal">(opcional)</span></label>
                            <input
                                type="url"
                                class="form-control @error('url') is-invalid @enderror"
                                id="notif_url"
                                name="url"
                                placeholder="https://elcartucho.com/productos"
                                value="{{ old('url') }}"
                            >
                            <div class="form-text">Si lo dejás vacío, abre la página principal de la app.</div>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Preview --}}
                        <div class="mb-4 p-3 rounded-3 border" style="background: #f8f9fa;">
                            <p class="text-muted small fw-semibold mb-2"><i class="fas fa-eye me-1"></i> Vista previa</p>
                            <div class="d-flex align-items-start gap-2">
                                <img src="{{ asset('icon.svg') }}" alt="icon" width="32" height="32" class="rounded" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><rect fill=%22%23232162%22 width=%2232%22 height=%2232%22 rx=%224%22/><text y=%2222%22 x=%224%22 font-size=%2218%22>🎮</text></svg>'">
                                <div>
                                    <p class="mb-0 fw-semibold small" id="preview-titulo" style="color: #1a1a2e;">Título de la notificación</p>
                                    <p class="mb-0 small text-muted" id="preview-mensaje">El mensaje aparecerá aquí...</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="background: var(--color-indigo); border-color: var(--color-indigo);" id="btn-enviar">
                            <i class="fas fa-paper-plane me-2"></i>
                            Enviar a {{ $totalSuscriptos }} {{ $totalSuscriptos === 1 ? 'usuario' : 'usuarios' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Historial --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 d-flex align-items-center gap-2" style="border-radius: 12px 12px 0 0;">
                <i class="fas fa-history text-muted"></i>
                <h6 class="m-0 fw-semibold">Historial de Notificaciones</h6>
            </div>
            <div class="card-body p-0">
                @if($logs->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Todavía no se enviaron notificaciones.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Notificación</th>
                                    <th class="text-center" style="width: 80px;">Enviadas</th>
                                    <th class="text-center" style="width: 80px;">Exitosas</th>
                                    <th class="text-center" style="width: 80px;">Fallidas</th>
                                    <th style="width: 140px;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="badge mt-1 px-2 py-1 {{ $log->tipo === 'nuevo_producto' ? 'bg-success' : 'bg-primary' }}" style="font-size: 10px;">
                                                    {{ $log->tipo === 'nuevo_producto' ? '🎮 Producto' : '📢 Custom' }}
                                                </span>
                                                <div>
                                                    <p class="mb-0 fw-semibold small">{{ $log->titulo }}</p>
                                                    <p class="mb-0 text-muted" style="font-size: 12px;">{{ Str::limit($log->mensaje, 60) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="fw-semibold">{{ $log->enviadas }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="text-success fw-semibold">{{ $log->exitosas }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="{{ $log->fallidas > 0 ? 'text-danger' : 'text-muted' }} fw-semibold">{{ $log->fallidas }}</span>
                                        </td>
                                        <td class="align-middle text-muted small">
                                            {{ $log->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tituloInput = document.getElementById('notif_titulo');
    const mensajeInput = document.getElementById('notif_mensaje');
    const tituloCount = document.getElementById('titulo-count');
    const mensajeCount = document.getElementById('mensaje-count');
    const previewTitulo = document.getElementById('preview-titulo');
    const previewMensaje = document.getElementById('preview-mensaje');

    if (!tituloInput) return;

    function updateCount(input, countEl, max) {
        const len = input.value.length;
        countEl.textContent = len + '/' + max;
        countEl.className = 'form-text text-end ' + (len > max * 0.8 ? 'text-warning' : '');
    }

    tituloInput.addEventListener('input', function () {
        updateCount(this, tituloCount, 100);
        previewTitulo.textContent = this.value || 'Título de la notificación';
    });

    mensajeInput.addEventListener('input', function () {
        updateCount(this, mensajeCount, 300);
        previewMensaje.textContent = this.value || 'El mensaje aparecerá aquí...';
    });

    // Confirmación antes de enviar
    document.getElementById('form-notificacion')?.addEventListener('submit', function (e) {
        const count = {{ $totalSuscriptos }};
        if (!confirm(`¿Confirmás el envío de esta notificación a ${count} usuario${count !== 1 ? 's' : ''}?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
