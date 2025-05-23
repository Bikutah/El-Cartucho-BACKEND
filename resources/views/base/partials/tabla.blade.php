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
                <div class="table-row">
                    {!! $renderFila($item) !!}
                    <div class="table-cell actions">
                        <span class="table-cell-label">Acciones</span>
                        <div>
                            @if (isset($rutaEditar))
                                <a href="{{ route($rutaEditar, $item) }}" 
                                class="action-btn"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
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
