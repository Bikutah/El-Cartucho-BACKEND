@if ($items->count())
    <div class="table-responsive-custom">
        {{-- ENCABEZADO - Solo visible en desktop --}}
        @if (isset($columnas) && is_array($columnas))
            <div class="table-header">
                @foreach ($columnas as $columna)
                    <div class="table-cell {{ $columna['class'] ?? '' }}">
                        {{ $columna['label'] ?? $columna }}
                    </div>
                @endforeach
                <div class="table-cell">Opciones</div>
            </div>
        @endif

        {{-- FILAS --}}
        <div class="table-container">
            @foreach ($items as $item)
                <div class="table-row" data-id="{{ $item->id }}">
                    {!! $renderFila($item) !!}
                    <div class="table-cell actions w-auto">
                        <span class="table-cell-label">Acciones</span>
                        <div class="d-flex gap-1">
                            @if (isset($rutaEditar))
                                <a href="{{ route($rutaEditar, $item) }}" 
                                class="action-btn"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                            @endif
                            @if (isset($rutaEliminar))
                                <button type="button"
                                        class="action-btn text-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEliminar"
                                        data-id="{{ $item->id }}"
                                        data-action="{{ route($rutaEliminar, $item) }}"
                                        title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-3 pagination-modern">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="text-center py-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
            <i class="fas fa-inbox text-gray-400 text-xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No hay elementos</h3>
        <p class="text-gray-500">No hay elementos para mostrar en este momento.</p>
    </div>
@endif

{{-- Modal global reutilizable --}}
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que querés eliminar este elemento?
            </div>
            <div class="modal-footer">
                <form id="formEliminar" method="POST" class="delete-form" onsubmit="return false;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnEliminar">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Eliminar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>



