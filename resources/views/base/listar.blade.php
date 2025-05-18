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

<div class="card shadow border-0 mb-4" style="background-color: rgba(255,255,255,0.05);">
    <div class="card-body">
        <div id="tabla-items">
            @include('base.partials.tabla', [
                'items' => $items,
                'columnas' => $columnas,
                'renderFila' => $renderFila,
                'rutaEditar' => $rutaEditar ?? null
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
