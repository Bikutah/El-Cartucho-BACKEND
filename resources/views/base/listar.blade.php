@extends('layouts.app')
@section('title', $titulo ?? 'Listado')
@section('content')
    <h1 class="h3 mb-4 text-color-primario font-weight-bold">{{ $titulo ?? 'Listado' }}</h1>
    @if (isset($rutaCrear))
        <a href="{{ route($rutaCrear) }}" class="btn btn-primary mb-3">Crear nuevo</a>
    @endif
    <div class="card shadow mb-4">
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
