@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-4 fw-bold" style="color: var(--color-primario);">
                    <i class="bi bi-images"></i> {{ $producto->nombre }} &gt; imágenes
                </h1>
                <a href="{{ route('productos.index') }}" class="mb-4 btn-back d-flex align-items-center">
                    <i class="fas fa-arrow-left me-2"></i> Volver
                </a>
            </div>

            @if ($imagenes->isEmpty())
                <div class="empty-state rounded-3 p-5 text-center">
                    <i class="fas fa-images fs-1 text-accent-custom opacity-75 mb-3"></i>
                    <h5 class="fw-medium">No hay imágenes disponibles</h5>
                    <p class="text-muted">No hay imágenes disponibles para este producto.</p>
                </div>
            @else
                <div id="carouselProducto" class="carousel slide mb-4 rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel" data-bs-touch="true">
                    <div class="carousel-inner rounded-4">
                        @foreach ($imagenes as $index => $imagen)
                            <div class="carousel-item @if($index === 0) active @endif">
                                <div class="main-image-container position-relative">
                                    <img src="{{ $imagen->imagen_url }}" 
                                         class="d-block w-100 rounded-4" 
                                         style="max-height: 500px; object-fit: contain;" 
                                         alt="Imagen {{ $index + 1 }}">

                                    <button type="button" class="btn-delete position-absolute bottom-0 end-0 m-3"
                                            style="z-index: 10;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEliminar"
                                            data-action="{{ route('imagenes.destroy', $imagen) }}">
                                        <i class="fas fa-trash-alt me-1"></i>
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev control-button" type="button" data-bs-target="#carouselProducto" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next control-button" type="button" data-bs-target="#carouselProducto" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>

                <div class="thumbnails-container d-flex justify-content-center gap-2 flex-wrap" id="miniaturas">
                    @foreach ($imagenes as $index => $imagen)
                        <div class="thumbnail-wrapper">
                            <img src="{{ $imagen->imagen_url }}"
                                 class="thumbnail @if($index === 0) active @endif"
                                 data-bs-target="#carouselProducto"
                                 data-bs-slide-to="{{ $index }}"
                                 alt="Miniatura {{ $index + 1 }}">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal de confirmación de eliminación --}}
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEliminarLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que querés eliminar esta imagen?
      </div>
      <div class="modal-footer">
        <form id="formEliminar" method="POST">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Eliminar</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('carouselProducto');
        const miniaturas = document.querySelectorAll('.thumbnail');
        
        const bsCarousel = new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            touch: true
        });

        // Actualizar miniaturas
        carousel.addEventListener('slide.bs.carousel', function(event) {
            miniaturas.forEach(img => img.classList.remove('active'));
            miniaturas[event.to].classList.add('active');
        });

        miniaturas.forEach((miniatura, index) => {
            miniatura.addEventListener('click', function() {
                bsCarousel.to(index);
            });
        });

        // Hover en imágenes
        const mainImages = document.querySelectorAll('.carousel-item img');
        mainImages.forEach(img => {
            img.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.03)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            img.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Modal dinámico: inyectar action al form
        const modalEliminar = document.getElementById('modalEliminar');
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            const form = modalEliminar.querySelector('#formEliminar');
            form.setAttribute('action', action);
        });
    });
</script>
@endpush
