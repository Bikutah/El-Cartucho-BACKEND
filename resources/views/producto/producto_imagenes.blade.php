@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-4">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden gallery-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="h4 mb-4 fw-bold" style="color: var(--color-primario);">
                    <i class="bi bi-images"></i> {{ $producto->nombre }} > imágenes
                </h4>
                <div class="d-flex gap-2">
                    @if ($imagenes->count() < 5)
                    <label for="input-imagen" class="mb-4 btn btn-success d-flex align-items-center" style="cursor: pointer;">
                        <i class="fas fa-plus me-2"></i> Agregar imagen
                    </label>
                    @endif
                    <form id="form-subir-imagen" action="{{ route('imagenes.store') }}" method="POST" enctype="multipart/form-data" class="d-none">
                        @csrf
                        <input
                            type="file"
                            id="input-imagen"
                            name="imagen"
                            class="form-control"
                            accept="image/*">
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                    </form>
                    <a href="{{ route('productos.index') }}" class="mb-4 btn btn-primary d-flex align-items-center">
                        <i class="fas fa-arrow-left me-2"></i> Volver
                    </a>
                </div>
            </div>

            @if ($imagenes->isEmpty())
                <div class="empty-state rounded-3 p-5 text-center">
                    <i class="fas fa-images fs-1 text-accent-custom opacity-75 mb-3"></i>
                    <h5 class="fw-medium">No hay imágenes disponibles</h5>
                    <p class="text-muted">No hay imágenes disponibles para este producto.</p>
                </div>
            @else
                <div id="carouselProducto" class="carousel slide mb-4 rounded-4 overflow-hidden shadow-sm gallery-carousel" data-bs-ride="carousel" data-bs-touch="true">
                    <div class="carousel-inner rounded-4">
                        @foreach ($imagenes as $index => $imagen)
                            <div class="carousel-item @if($index === 0) active @endif">
                                <div class="main-image-container position-relative">
                                    <img src="{{ $imagen->imagen_url }}" 
                                         class="d-block w-100 rounded-4 main-image" 
                                         alt="Imagen {{ $index + 1 }}">
                                    <button type="button" class="btn-delete position-absolute bottom-0 end-0 m-3"
                                            style="z-index: 10;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEliminar"
                                            data-action="{{ route('imagenes.destroy', $imagen) }}"
                                            data-slide-index="{{ $index }}">
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

                <div class="thumbnails-container d-flex justify-content-center gap-3 flex-wrap" id="miniaturas">
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
                    <button type="submit" class="btn btn-danger" id="confirmarEliminar">
                        <span class="btn-text">Eliminar</span>
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar elementos
    const cardBody = $('.card-body');
    let btnAgregar = $('label[for="input-imagen"]'); // Usar let para permitir reasignación
    const inputImagen = $('#input-imagen');
    let carousel = $('#carouselProducto');
    let carouselInner = $('.carousel-inner');
    let miniaturas = $('.thumbnail');
    let bsCarousel = null;
    let miniaturasContainer = $('#miniaturas');

    // Función para inicializar o reinicializar el carrusel
    function inicializarCarrusel() {
        if (carousel.length) {
            if (bsCarousel) {
                bsCarousel.dispose(); // Destruir instancia previa si existe
            }
            bsCarousel = new bootstrap.Carousel(carousel[0], {
                interval: 5000,
                wrap: true,
                touch: true,
                keyboard: true
            });
        }
    }

    // Verificar el número de imágenes al cargar la página
    if (carouselInner.length && carouselInner.children().length >= 5) {
        btnAgregar.hide();
    }

    // Inicializar carrusel si existe
    if (carousel.length) {
        inicializarCarrusel();

        // Sincronizar miniaturas con el carrusel
        carousel.on('slide.bs.carousel', function(event) {
            miniaturas.removeClass('active');
            miniaturas.eq(event.to).addClass('active');
        });

        // Evento click en miniaturas
        miniaturas.on('click', function() {
            const index = $(this).data('bs-slide-to');
            bsCarousel.to(index);
        });
    }

    // Manejar la subida de imágenes
    inputImagen.on('change', function() {
        const form = $('#form-subir-imagen');
        const formData = new FormData(form[0]);

        // Deshabilitar botón y mostrar estado de carga con spinner de Bootstrap
        btnAgregar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Subiendo...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(data) {
                // Restaurar botón
                btnAgregar.prop('disabled', false).html('<i class="fas fa-plus me-2"></i> Agregar imagen');

                // Verificar si hay error
                if (!data.success || !data.imagen_url || !data.imagen_id) {
                    alert(data.error || 'Respuesta inválida del servidor.');
                    return;
                }

                // Si no hay carrusel (primer imagen), crear la estructura
                if (!carousel.length) {
                    // Remover el estado vacío si existe
                    $('.empty-state').remove();

                    // Crear la estructura del carrusel
                    const carouselHtml = `
                        <div id="carouselProducto" class="carousel slide mb-4 rounded-4 overflow-hidden shadow-sm gallery-carousel" data-bs-ride="carousel" data-bs-touch="true">
                            <div class="carousel-inner rounded-4"></div>
                            <button class="carousel-control-prev control-button" type="button" data-bs-target="#carouselProducto" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next control-button" type="button" data-bs-target="#carouselProducto" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                                <span class="visually-hidden">Siguiente</span>
                            </button>
                        </div>
                    `;
                    cardBody.append(carouselHtml);

                    // Actualizar referencias
                    carousel = $('#carouselProducto');
                    carouselInner = $('.carousel-inner');

                    // Crear el contenedor de miniaturas si no existe
                    if (!miniaturasContainer.length) {
                        const miniaturasHtml = `
                            <div class="thumbnails-container d-flex justify-content-center gap-3 flex-wrap" id="miniaturas"></div>
                        `;
                        cardBody.append(miniaturasHtml);
                        miniaturasContainer = $('#miniaturas');
                    }
                }

                // Determinar si el nuevo slide debe ser activo
                const totalItems = carouselInner.children().length;
                const active = totalItems === 0 ? 'active' : '';

                // Agregar nuevo slide al carrusel
                const nuevaSlide = `
                    <div class="carousel-item ${active}">
                        <div class="main-image-container position-relative">
                            <img src="${data.imagen_url}" class="d-block w-100 rounded-4 main-image" alt="Nueva imagen">
                            <button type="button" class="btn-delete position-absolute bottom-0 end-0 m-3"
                                    style="z-index: 10;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEliminar"
                                    data-action="/imagenes/${data.imagen_id}"
                                    data-slide-index="${totalItems}">
                                <i class="fas fa-trash-alt me-1"></i>
                                <span>Eliminar</span>
                            </button>
                        </div>
                    </div>
                `;
                carouselInner.append(nuevaSlide);

                // Agregar nueva miniatura
                const thumbnail = `
                    <div class="thumbnail-wrapper">
                        <img src="${data.imagen_url}" 
                             class="thumbnail ${active}" 
                             data-bs-target="#carouselProducto" 
                             data-bs-slide-to="${totalItems}" 
                             alt="Miniatura">
                    </div>
                `;
                miniaturasContainer.append(thumbnail);

                // Actualizar miniaturas
                miniaturas = $('.thumbnail');

                // Agregar evento click a la nueva miniatura
                miniaturasContainer.find('img[data-bs-slide-to="' + totalItems + '"]').on('click', function() {
                    bsCarousel.to(totalItems);
                });

                // Ocultar botón si hay 5 o más imágenes
                if (carouselInner.children().length + 1 >= 5) {
                    btnAgregar.hide();
                }

                // Inicializar o reinicializar el carrusel
                inicializarCarrusel();
            },
            error: function(xhr) {
                console.error('Error en la subida:', xhr.responseJSON);
                // Restaurar botón
                btnAgregar.prop('disabled', false).html('<i class="fas fa-plus me-2"></i> Agregar imagen');
                alert('Ocurrió un error al subir la imagen: ' + (xhr.responseJSON?.error || 'Error desconocido'));
            }
        });
    });

    // Efecto hover en imágenes
    cardBody.on('mouseenter', '.carousel-item img', function() {
        $(this).css({
            transform: 'scale(1.03)',
            transition: 'transform 0.3s ease'
        });
    }).on('mouseleave', '.carousel-item img', function() {
        $(this).css({ transform: 'scale(1)' });
    });

    // Configurar acción del formulario de eliminación
    $('#modalEliminar').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const action = button.data('action');
        const slideIndex = button.data('slide-index');
        $('#formEliminar').attr('action', action).data('slide-index', slideIndex);
    });

    // Manejar la eliminación con AJAX
    $('#formEliminar').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const url = form.attr('action');
        const slideIndex = form.data('slide-index');
        const csrfToken = $('input[name="_token"]').val();
        const btnEliminar = $('#confirmarEliminar');

        // Mostrar spinner y deshabilitar botón
        btnEliminar.prop('disabled', true);
        btnEliminar.find('.btn-text').text('Eliminando...');
        btnEliminar.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: url,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(data) {
                // Restaurar botón
                btnEliminar.prop('disabled', false);
                btnEliminar.find('.btn-text').text('Eliminar');
                btnEliminar.find('.spinner-border').addClass('d-none');

                // Cerrar el modal y limpiar backdrop
                $('#modalEliminar').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');

                // Verificar si hay error
                if (!data.success) {
                    alert(data.error || 'Error al eliminar la imagen.');
                    return;
                }

                // Remover el slide correspondiente
                const slide = carouselInner.find('.carousel-item').eq(slideIndex);
                const isActive = slide.hasClass('active');
                slide.remove();

                // Remover la miniatura correspondiente
                miniaturasContainer.find('.thumbnail-wrapper').eq(slideIndex).remove();

                // Actualizar referencias de miniaturas
                miniaturas = $('.thumbnail');

                // Actualizar índices de las miniaturas restantes
                miniaturas.each(function(index) {
                    $(this).attr('data-bs-slide-to', index);
                });

                // Actualizar índices de los botones de eliminación restantes
                carouselInner.find('.btn-delete').each(function(index) {
                    $(this).attr('data-slide-index', index);
                });

                // Si se eliminó el slide activo, establecer el primer slide como activo
                if (carouselInner.children().length > 0 && isActive) {
                    carouselInner.children().first().addClass('active');
                    miniaturas.first().addClass('active');
                }

                // Si no hay imágenes, remover el carrusel y mostrar estado vacío
                if (carouselInner.children().length === 0) {
                    carousel.remove();
                    carousel = null;
                    carouselInner = null;
                    if (bsCarousel) {
                        bsCarousel.dispose();
                        bsCarousel = null;
                    }
                    miniaturasContainer.remove();
                    miniaturasContainer = null;

                    const emptyStateHtml = `
                        <div class="empty-state rounded-3 p-5 text-center">
                            <i class="fas fa-images fs-1 text-accent-custom opacity-75 mb-3"></i>
                            <h5 class="fw-medium">No hay imágenes disponibles</h5>
                            <p class="text-muted">No hay imágenes disponibles para este producto.</p>
                        </div>
                    `;
                    cardBody.append(emptyStateHtml);
                }

                // Mostrar el botón de agregar imagen si hay menos de 5 imágenes
                if (!carouselInner.length || carouselInner.children().length < 5) {
                    if (!btnAgregar.length) {
                        const addButtonHtml = `
                            <label for="input-imagen" class="mb-4 btn btn-success d-flex align-items-center" style="cursor: pointer;">
                                <i class="fas fa-plus me-2"></i> Agregar imagen
                            </label>
                        `;
                        $('#form-subir-imagen').parent().prepend(addButtonHtml);
                        btnAgregar = $('label[for="input-imagen"]'); // Reasignar btnAgregar
                    } else {
                        btnAgregar.show();
                    }
                }

                // Reinicializar el carrusel
                inicializarCarrusel();
            },
            error: function(xhr) {
                console.error('Error al eliminar:', xhr.responseJSON);
                // Restaurar botón
                btnEliminar.prop('disabled', false);
                btnEliminar.find('.btn-text').text('Eliminar');
                btnEliminar.find('.spinner-border').addClass('d-none');
                // Cerrar el modal y limpiar backdrop
                $('#modalEliminar').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                alert('Error al eliminar la imagen: ' + (xhr.responseJSON?.error || 'Error desconocido'));
            }
        });
    });
});
</script>
@endpush
@endsection