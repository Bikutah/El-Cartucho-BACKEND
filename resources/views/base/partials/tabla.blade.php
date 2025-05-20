@if ($items->count())
    @if (isset($columnas) && is_array($columnas))
        <div class="row g-2 border-bottom pb-2 mb-3 px-2 py-1 rounded" style="background-color: var(--color-indigo-dark) !important; color: #fff; font-weight: bold;">
            @foreach ($columnas as $columna)
                <div class="col">{{ $columna }}</div>
            @endforeach
            <div class="col">Acciones</div>
        </div>
    @endif

    @foreach ($items as $item)
        <div class="row g-2 align-items-center border-bottom py-2 px-2" style="color: var(--color-texto);">
            {!! $renderFila($item) !!}
            
            <div class="col d-flex justify-center">
                @if (isset($rutaEditar))
                    <a href="{{ route($rutaEditar, $item) }}" class="btn btn-sm d-inline-flex align-items-center justify-content-center"
                        style="background-color: var(--color-secundario); border: none; color: var(--color-terciario);"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                        <i class="fas fa-pen"></i>
                    </a>
                @endif
                @if (method_exists($item, 'imagenes') && $item->imagenes && $item->imagenes->isNotEmpty())
                    <a href="{{ route('productos.imagenes', $item->id) }}"
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Ver imágenes">
                        <i class="fas fa-images"></i>
                    </a>
                @else
                    <span class="badge bg-secondary">Sin imagen</span>
                @endif
            </div>
        </div>
    @endforeach
    <div class="mt-3 pagination-modern">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
@else
    <p>No hay elementos para mostrar.</p>
@endif