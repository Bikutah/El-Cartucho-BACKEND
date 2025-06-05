@extends('layouts.app')

@section('title', $titulo ?? 'Listado')

@section('content')
<h1 class="h3 mb-4 fw-bold" style="color: var(--color-primario);">
    {{ $titulo ?? 'Listado' }}
</h1>

@if (isset($rutaCrear))
    <a href="{{ route($rutaCrear) }}" class="btn btn-primary mb-3">
        <i class="fas fa-plus me-1"></i> Crear nuevo
    </a>
@endif

@if (isset($filtros))
    <form method="GET" action="{{ request()->url() }}" class="mb-3">
        @csrf
        <div class="row g-2">
            @foreach ($filtros as $filtro)
                <div class="col-md">
                    @if (isset($filtro['type']) && $filtro['type'] === 'select' && isset($filtro['options']))
                        <select name="{{ $filtro['name'] }}" class="form-select">
                            <option value="">{{ $filtro['placeholder'] ?? 'Seleccione una opción' }}</option>
                            @foreach ($filtro['options'] as $value => $label)
                                <option value="{{ $value }}" {{ request($filtro['name']) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text"
                            name="{{ $filtro['name'] }}"
                            class="form-control"
                            placeholder="{{ $filtro['placeholder'] ?? Str::title($filtro['name']) }}"
                            value="{{ request($filtro['name']) }}">
                    @endif
                </div>
            @endforeach

            <div class="col-md-auto">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <a href="{{ request()->url() }}" class="btn btn-outline-danger ms-2">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
@endif

<div class="card shadow border-0 mb-4" style="background-color: rgba(255,255,255,0.05);">
    <div class="card-body">
        <div id="tabla-items">
            @include('base.partials.tabla', [
                'items' => $items,
                'columnas' => $columnas,
                'renderFila' => $renderFila,
                'rutaEditar' => $rutaEditar ?? null,
                'rutaEliminar' => $rutaEliminar ?? null
            ])
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function () {
    // Paginación AJAX
    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function() {
                $('#tabla-items').html('<div class="text-center py-4"><div class="spinner-border" role="status" style="color: var(--color-indigo-light);"><span class="visually-hidden">Cargando...</span></div></div>');
            },
            success: function (data) {
                $('#tabla-items').html(data);
                // Reinicializar tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            },
            error: function () {
                console.error('Error al cargar la página');
            }
        });
    });

    // Manejo del modal y eliminación
    let currentRowId = null;

    $(document).on('show.bs.modal', '#modalEliminar', function (event) {
        console.log('Evento show.bs.modal disparado');
        const button = event.relatedTarget;
        if (!button) {
            console.error('Error: Botón relacionado no encontrado');
            return;
        }

        currentRowId = button.getAttribute('data-id');
        console.log('ID del producto:', currentRowId);
    });

    $(document).on('submit', '#formEliminar', function (e) {
        e.preventDefault();
        console.log('Evento submit disparado');

        if (!currentRowId) {
            console.error('Error: ID del producto no definido');
            return;
        }

        const action = `/productos/${currentRowId}`;
        console.log('Enviando solicitud a:', action);

        // Mostrar spinner y deshabilitar botón
        const btnEliminar = document.getElementById('btnEliminar');
        const btnText = btnEliminar.querySelector('.btn-text');
        const spinner = btnEliminar.querySelector('.spinner-border');
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');
        btnEliminar.disabled = true;

        fetch(action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(this)
        })
        .then(response => {
            console.log('Respuesta del servidor:', response);
            if (!response.ok) {
                throw new Error(`Error al eliminar: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            const row = document.querySelector(`.table-row[data-id="${currentRowId}"]`);
            if (row) {
                row.remove();
                console.log('Fila eliminada:', currentRowId);
            } else {
                console.warn('No se encontró la fila con ID:', currentRowId);
            }

            const bsModal = bootstrap.Modal.getInstance(document.getElementById('modalEliminar'));
            if (bsModal) {
                bsModal.hide();
                console.log('Modal cerrado');
            } else {
                console.error('Error: No se pudo cerrar el modal');
            }

            // Insertar alerta de éxito
            const alertContainer = document.getElementById('alert-container');
            if (alertContainer) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div>
                            Producto eliminado correctamente
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
                // Inicializar Bootstrap para la alerta
                const bsAlert = new bootstrap.Alert(alertDiv);
                // Cerrar automáticamente después de 8 segundos
                setTimeout(() => {
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 8000); // 8 segundos
            } else {
                console.error('Error: Contenedor de alertas no encontrado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Opcional: Insertar alerta de error si quieres
        })
        .finally(() => {
            // Restaurar botón
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            btnEliminar.disabled = false;
        });
    });
});
</script>
@endpush