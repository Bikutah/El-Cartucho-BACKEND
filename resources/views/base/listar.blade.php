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
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    })
                },
                error: function () {
                    alert('Error al cargar la página.');
                }
            });
        });
    });
</script>
@endpush
