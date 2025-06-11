@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-4">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden gallery-card">
        <div class="card-body p-4">
            <div class="header-section">
                <div class="header-content">
                    <!-- Título principal -->
                    <div class="header-title-wrapper">
                        <h4 class="header-title">
                            <i class="bi bi-images header-icon"></i>
                            <span>{{ $producto->nombre }}</span>
                        </h4>
                    </div>

                    <!-- Acciones del header -->
                    <div class="header-actions">
                        @if ($imagenes->count() < 5)
                            <label for="input-imagen" class="btn-add-image">
                            <i class="fas fa-plus"></i>
                            <span class="btn-text">Agregar imagen</span>
                            </label>
                            @endif

                            <form id="form-subir-imagen" action="{{ route('imagenes.store') }}" method="POST" enctype="multipart/form-data" class="hidden-form">
                                @csrf
                                <input
                                    type="file"
                                    id="input-imagen"
                                    name="imagen"
                                    class="file-input"
                                    accept="image/*">
                                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                            </form>

                            <a href="{{ route('productos.index') }}" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                <span class="btn-text">Volver</span>
                            </a>
                    </div>
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
        let btnAgregar = $('label[for="input-imagen"]');
        let carousel = $('#carouselProducto');
        let carouselInner = $('.carousel-inner');
        let miniaturas = $('.thumbnail');
        let bsCarousel = null;
        let miniaturasContainer = $('#miniaturas');

        // Función para inicializar o reinicializar el carrusel y sus eventos
        function inicializarCarrusel() {
            if (carousel.length) {
                if (bsCarousel) {
                    bsCarousel.dispose();
                }
                bsCarousel = new bootstrap.Carousel(carousel[0], {
                    interval: 5000,
                    wrap: true,
                    touch: true,
                    keyboard: true
                });

                // Registrar eventos del carrusel
                carousel.off('slide.bs.carousel').on('slide.bs.carousel', function(event) {
                    miniaturas.removeClass('active');
                    miniaturas.eq(event.to).addClass('active');
                });

                // Registrar eventos de miniaturas
                miniaturas.off('click').on('click', function() {
                    const index = $(this).data('bs-slide-to');
                    bsCarousel.to(index);
                });
            }
        }

        // Función para actualizar el estado del botón de agregar imagen
        function actualizarEstadoBoton() {
            btnAgregar = $('label[for="input-imagen"]');
            if (carouselInner.length && carouselInner.children().length >= 5) {
                if (btnAgregar.length) {
                    btnAgregar.addClass('d-none');
                }
            } else {
                if (!btnAgregar.length) {
                    const addButtonHtml = `
                    <label for="input-imagen" class="mb-4 btn btn-success d-flex align-items-center" style="cursor: pointer;">
                        <i class="fas fa-plus me-2 d-block"></i> Agregar imagen
                    </label>
                `;
                    $('#form-subir-imagen').parent().prepend(addButtonHtml);
                    btnAgregar = $('label[for="input-imagen"]');
                } else {
                    btnAgregar.removeClass('d-none');
                }
            }
            const inputImagen = $('#input-imagen');
            if (inputImagen.length) {
                inputImagen.val('');
            }
        }

        // Verificar el número de imágenes al cargar la página
        actualizarEstadoBoton();

        // Inicializar carrusel si existe al cargar
        inicializarCarrusel();

        // Manejar la subida de imágenes con delegación de eventos
        $(document).on('change', '#input-imagen', function(event) {
            const input = $(this);
            const form = $('#form-subir-imagen');
            if (!form.length) {
                alert("Error: El formulario para subir imágenes no está disponible.");
                input.val('');
                return;
            }

            const formData = new FormData(form[0]);
            const file = input[0].files[0];
            if (!file) {
                alert("Por favor, selecciona una imagen válida.");
                input.val('');
                return;
            }

            btnAgregar = $('label[for="input-imagen"]');
            if (btnAgregar.length) {
                btnAgregar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Subiendo...');
            }

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
                },
                success: function(data) {
                    if (btnAgregar.length) {
                        btnAgregar.prop('disabled', false).html('<i class="fas fa-plus me-2"></i> Agregar imagen');
                    }
                    input.val('');

                    if (!data.success || !data.imagen_url || !data.imagen_id) {
                        alert(data.error || 'Respuesta inválida del servidor.');
                        return;
                    }

                    // Crear o reparar carrusel si no existe o está incompleto
                    if (!carousel.length || !carousel.find('.carousel-inner').length) {
                        $('.empty-state').remove();
                        $('#carouselProducto').remove(); // Eliminar cualquier carrusel residual
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
                        carousel = $('#carouselProducto');
                        carouselInner = carousel.find('.carousel-inner');

                        if (!miniaturasContainer.length) {
                            const miniaturasHtml = `
                            <div class="thumbnails-container d-flex justify-content-center gap-3 flex-wrap" id="miniaturas"></div>
                        `;
                            cardBody.append(miniaturasHtml);
                            miniaturasContainer = $('#miniaturas');
                        }
                    }

                    // Asegurar que carouselInner esté definido
                    if (!carouselInner.length) {
                        carouselInner = carousel.find('.carousel-inner');
                    }

                    const totalItems = carouselInner.children().length;
                    const active = totalItems === 0 ? 'active' : '';

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

                    const thumbnail = `
                    <div class="thumbnail-wrapper">
                        <img src="${data.imagen_url}" 
                             class="thumbnail ${active}" 
                             data-bs-target="#carouselProducto" 
                             data-bs-slide-to="${totalItems}" 
                             alt="Miniatura">
                    </div>
                `;
                    miniaturasContainer.append($(thumbnail));

                    miniaturas = $('.thumbnail');
                    miniaturasContainer.find('img[data-bs-slide-to="' + totalItems + '"]').on('click', function() {
                        if (bsCarousel) {
                            bsCarousel.to(totalItems);
                        }
                    });

                    actualizarEstadoBoton();
                    inicializarCarrusel();
                },
                error: function(xhr) {
                    if (btnAgregar.length) {
                        btnAgregar.prop('disabled', false).html('<i class="fas fa-plus me-2"></i> Agregar imagen');
                    }
                    input.val('');
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
            $(this).css({
                transform: 'scale(1)'
            });
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
                    btnEliminar.prop('disabled', false);
                    btnEliminar.find('.btn-text').text('Eliminar');
                    btnEliminar.find('.spinner-border').addClass('d-none');

                    $('#modalEliminar').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');

                    if (!data.success) {
                        alert(data.error || 'Error al eliminar la imagen.');
                        return;
                    }

                    const slide = carouselInner.find('.carousel-item').eq(slideIndex);
                    const isActive = slide.hasClass('active');
                    slide.remove();

                    miniaturasContainer.find('.thumbnail-wrapper').eq(slideIndex).remove();
                    miniaturas = $('.thumbnail');

                    miniaturas.each(function(index) {
                        $(this).attr('data-bs-slide-to', index);
                    });

                    carouselInner.find('.btn-delete').each(function(index) {
                        $(this).attr('data-slide-index', index);
                    });

                    if (carouselInner.children().length > 0 && isActive) {
                        carouselInner.children().first().addClass('active');
                        miniaturas.first().addClass('active');
                    }

                    if (carouselInner.children().length === 0) {
                        carousel.hide();
                        carousel = 0;
                        carouselInner = 0;
                        if (bsCarousel) {
                            bsCarousel.dispose();
                            bsCarousel = 0;
                        }
                        miniaturasContainer.remove();
                        miniaturasContainer = 0;

                        const emptyStateHtml = `
                        <div class="empty-state rounded-3 p-5 text-center">
                            <i class="fas fa-images fs-1 text-accent-custom opacity-75 mb-3"></i>
                            <h5 class="fw-medium">No hay imágenes disponibles</h5>
                            <p class="text-muted">No hay imágenes disponibles para este producto.</p>
                        </div>
                    `;
                        cardBody.append(emptyStateHtml);
                    }

                    actualizarEstadoBoton();
                    inicializarCarrusel();
                },
                error: function(xhr) {
                    btnEliminar.prop('disabled', false);
                    btnEliminar.find('.btn-text').text('Eliminar');
                    btnEliminar.find('.spinner-border').addClass('d-none');
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