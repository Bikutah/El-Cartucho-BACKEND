@if ($items->count())
    <div class="row border-bottom pb-2 mb-2 bg-primary text-white rounded py-2 font-weight-bold">
        @foreach ($columnas as $columna)
            <div class="col">{{ $columna }}</div>
        @endforeach
        <div class="col text-end">Acciones</div>
    </div>
    @foreach ($items as $item)
        <div class="row align-items-center border-bottom py-2">
            {!! $renderFila($item) !!}
            <div class="col text-end">
                @if ($rutaEditar)
                    <a href="{{ route($rutaEditar, $item) }}" class="btn btn-primary btn-sm me-2">
                        <i class="fa fa-pen"></i>
                    </a>
                @endif
            </div>
        </div>
    @endforeach
    <div class="row mt-4">
        <div class="col d-flex justify-content-end">
            <div class="pagination-modern">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@else
    <p>No hay elementos para mostrar.</p>
@endif

