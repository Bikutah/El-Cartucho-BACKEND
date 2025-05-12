@extends('layouts.app')
@section('title', $titulo ?? 'Listado')
@section('content')
<h1 class="h3 mb-4 text-color-primario font-weight-bold">{{ $titulo ?? 'Listado' }}</h1>
@if (isset($rutaCrear))
    <a href="{{ route($rutaCrear) }}" class="btn btn-primary mb-3">Crear nuevo</a>
@endif
<div class="card shadow mb-4">
    <div class="card-body">
        @if (isset($encabezados) && is_array($encabezados))
            <div class=" row border-bottom pb-2 mb-2 bg-primary text-white rounded py-2 font-weight-bold">
                @foreach ($encabezados as $encabezado)
                    <div class="col">{{ $encabezado }}</div>
                @endforeach
                <div class="col text-end">Acciones</div>
            </div>
        @endif
        @foreach ($items as $item)
            <div class="row align-items-center border-bottom py-2">
                {!! $itemTexto($item) !!}
                <div class="col text-end">
                    @if (isset($rutaEditar))
                    <a href="{{ route($rutaEditar, $item) }}" class="btn btn-primary btn-sm me-2" 
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                        <i class="fa fa-pen"></i>
                    </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
