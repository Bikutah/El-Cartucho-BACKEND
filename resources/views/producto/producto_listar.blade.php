@extends('base.listar')
@php
    $titulo = 'Listado de Productos';
    $rutaCrear = 'productos.create';
    $rutaEditar = 'productos.edit';
    $columnas = ['Id','Nombre', 'Descripción','Precio','Stock','Categoría'];
    $items = $productos;
    $renderFila = function($producto) {
        $html = '
            <div class="col">' . e($producto->id) . '</div>
            <div class="col">' . e($producto->nombre) . '</div>
            <div class="col">' . e($producto->descripcion) . '</div>
            <div class="col">$' . number_format($producto->precioUnitario, 2, ',', '.') . '</div>
            <div class="col">' . e($producto->stock) . '</div>
            <div class="col">' . e(optional($producto->categoria)->nombre ?? 'Sin categoría') . '</div>
        ';
        $modalHtml = '';

        if ($producto->imagenes->isNotEmpty()) {
            $modalHtml = '
                <div class="modal fade" id="imagenesModal' . $producto->id . '" tabindex="-1" role="dialog" aria-labelledby="imagenesModalLabel' . $producto->id . '" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-images mr-2"></i>Imágenes de ' . e($producto->nombre) . '
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0">
                                <div id="carousel' . $producto->id . '" class="carousel slide" data-ride="carousel">
                                    <ol class="carousel-indicators">';
                                    foreach ($producto->imagenes as $index => $imagen) {
                                        $modalHtml .= '<li data-target="#carousel' . $producto->id . '" data-slide-to="' . $index . '" ' . ($index === 0 ? 'class="active"' : '') . '></li>';
                                    }
                                    $modalHtml .= '</ol>
                                    <div class="carousel-inner">';
                                        foreach ($producto->imagenes as $index => $imagen) {
                                            $modalHtml .= '
                                            <div class="carousel-item ' . ($index === 0 ? 'active' : '') . '">
                                                <img src="' . e($imagen->url) . '" class="d-block w-100" alt="Imagen de ' . e($producto->nombre) . '">
                                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded py-1">
                                                    <p class="mb-0">Imagen ' . ($index + 1) . ' de ' . $producto->imagenes->count() . '</p>
                                                </div>
                                            </div>';
                                        }
                                    $modalHtml .= '
                                    </div>
                                    <a class="carousel-control-prev" href="#carousel' . $producto->id . '" role="button" data-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Anterior</span>
                                    </a>
                                    <a class="carousel-control-next" href="#carousel' . $producto->id . '" role="button" data-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Siguiente</span>
                                    </a>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>';
        }
        return $html . $modalHtml;
    };
@endphp

@push('styles')
<style>
    .carousel-item {
        height: 400px;
        background-color: #f8f9fa;
    }
    
    .carousel-item img {
        height: 100%;
        object-fit: contain;
    }
    
    .carousel-indicators {
        margin-bottom: 0;
    }
    
    .carousel-caption {
        background-color: rgba(0, 0, 0, 0.5);
        border-radius: 5px;
        padding: 5px 10px;
        bottom: 10px;
    }
    
    .modal-lg {
        max-width: 800px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    // Pausar el carrusel cuando se abre el modal
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('.carousel').carousel('pause');
    });
    
    // Reiniciar el carrusel cuando se cierra el modal
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('.carousel').carousel(0);
    });
    
    // Controles de teclado para el carrusel
    $(document).on('keydown', function(e) {
        const $activeModal = $('.modal.show');
        if ($activeModal.length) {
            const $carousel = $activeModal.find('.carousel');
            if (e.keyCode === 37) { // Flecha izquierda
                $carousel.carousel('prev');
            } else if (e.keyCode === 39) { // Flecha derecha
                $carousel.carousel('next');
            }
        }
    });
});
</script>
@endpush