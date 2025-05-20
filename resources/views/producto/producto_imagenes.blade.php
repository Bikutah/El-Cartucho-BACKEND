@extends('layouts.app')

@php
    $titulo = 'Imágenes del Producto';
@endphp

@section('content')
<div class="container mt-4">
    <h3 class="mb-4">{{ $titulo }}</h3>

    @if ($imagenes->isEmpty())
        <div class="alert alert-info">No hay imágenes disponibles para este producto.</div>
    @else
        <div class="row">
            @foreach ($imagenes as $imagen)
                <div class="col-md-3 mb-4">
                    <div class="card shadow-sm">
                        <img src="{{ $imagen->imagen_url }}" class="card-img-top rounded" alt="Imagen del producto">
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <a href="{{ route('productos.index') }}" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>
@endsection
