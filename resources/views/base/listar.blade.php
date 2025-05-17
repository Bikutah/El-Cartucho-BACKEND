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
                @if (isset($columnas) && is_array($columnas))
            <div class="row g-2 border-bottom pb-2 mb-3 px-2 py-1 rounded" style="background-color: var(--color-primario); color: #1a1040; font-weight: bold;">

            @foreach ($columnas as $columna)
                <div class="col">{{ $columna }}</div>
            @endforeach
                <div class="col text-end">Acciones</div>
            </div>
        @endif

        @foreach ($items as $item)
            <div class="row g-2 align-items-center border-bottom py-2 px-2" style="color: var(--color-texto);">
                {!! $renderFila($item) !!}
                <div class="col text-end">
                    @if (isset($rutaEditar))
                        <a href="{{ route($rutaEditar, $item) }}" 
                           class="btn btn-sm d-inline-flex align-items-center justify-content-center" 
                           style="background-color: var(--color-secundario); border: none; color: var(--color-terciario);"
                           data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                            <i class="fas fa-pen"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '#tabla-items .pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                $('#tabla-items').html(data);
            },
            error: function() {
                alert('Error al cargar los datos.');
            }
        });
    });
});
</script>
@endpush
