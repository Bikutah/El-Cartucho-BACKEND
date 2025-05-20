@extends('layouts.app')

@php
    $titulo = 'Imágenes del Producto';
@endphp

@section('content')
<div class="container mt-4">
    <h3 class="mb-4">{{ $titulo }}</h3>

    <a href="{{ route('productos.index') }}" class="btn btn-secondary mb-4">
        <i class="fas fa-arrow-left"></i> Volver
    </a>

    @if ($imagenes->isEmpty())
        <div class="alert alert-info">No hay imágenes disponibles para este producto.</div>
    @else
        <!-- Slideshow principal -->
        <div id="carouselProducto" class="carousel slide mb-3" data-bs-ride="carousel">
            <div class="carousel-inner">

                @foreach ($imagenes as $index => $imagen)
                <div class="carousel-item @if($index === 0) active @endif">
                    <div class="position-relative" style="height: 500px;">
                        <!-- Imagen -->
                        <img src="{{ $imagen->imagen_url }}"
                            class="d-block w-100 rounded shadow"
                            style="height: 100%; object-fit: cover;"
                            alt="Imagen {{ $index }}">

                        <!-- Botón centrado sobre la imagen -->
                        <form action="{{ route('imagenes.destroy', $imagen) }}" method="POST"
                            class="position-absolute top-50 start-50"
                            style="z-index: 10;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm shadow"
                                    onclick="return confirm('¿Estás seguro de que querés eliminar esta imagen?')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach

            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselProducto" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselProducto" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>

        <!-- Miniaturas -->
        <div class="d-flex justify-content-center gap-2 flex-wrap" id="miniaturas">
            @foreach ($imagenes as $index => $imagen)
                <img src="{{ $imagen->imagen_url }}"
                     class="img-thumbnail miniatura @if($index === 0) border-primary border-3 @endif"
                     data-bs-target="#carouselProducto"
                     data-bs-slide-to="{{ $index }}"
                     style="width: 100px; height: 75px; object-fit: cover; cursor: pointer;"
                     alt="Miniatura {{ $index }}">
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    const carousel = document.getElementById('carouselProducto');
    const miniaturas = document.querySelectorAll('.miniatura');

    carousel.addEventListener('slide.bs.carousel', function (event) {
        miniaturas.forEach(img => img.classList.remove('border-primary', 'border-3'));
        miniaturas[event.to].classList.add('border-primary', 'border-3');
    });
</script>
@endpush
@endsection
