@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('title', $titulo ?? 'Formulario')

@section('content')
<h1 class="h3 mb-4 fw-bold" style="color: var(--color-primario);">
    {{ $titulo ?? 'Formulario' }}
</h1>

<div class="card shadow border-0 mb-4" style="background-color: rgba(255,255,255,0.05);">
    <div class="card-body">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($method) && strtoupper($method) !== 'POST')
                @method($method)
            @endif

            @foreach ($campos as $campo)
                @php
                    $name = $campo['name'];
                    $type = $campo['type'] ?? 'text';
                    $label = $campo['label'] ?? Str::title($name);
                    $placeholder = $campo['placeholder'] ?? '';
                    $value = old($name, $campo['value'] ?? '');
                    $multiple = !empty($campo['multiple']);
                    $id = Str::slug($name, '_');
                @endphp

                <div class="mb-3">
                    <label for="{{ $id }}" class="form-label fw-semibold" style="color: var(--color-secundario);">
                        {{ $label }}
                    </label>

                    {{-- Campo SELECT --}}
                    @if ($type === 'select')
                        <select
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-select"
                        >
                            <option value="">{{ $placeholder ?: 'Seleccione una opción' }}</option>
                            @foreach (($campo['options'] ?? []) as $optionValue => $optionText)
                                <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
                                    {{ $optionText }}
                                </option>
                            @endforeach
                        </select>

                    {{-- Campo TEXTAREA --}}
                    @elseif ($type === 'textarea')
                        <textarea
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-control"
                            placeholder="{{ $placeholder }}"
                        >{{ $value }}</textarea>

                    {{-- Campo FILE --}}
                    @elseif ($type === 'file')
                        <input
                            type="file"
                            id="{{ $id }}"
                            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                            class="form-control"
                            {{ $multiple ? 'multiple' : '' }}
                        >

                        {{-- Vista previa solo para campo "imagen" --}}
                        @if ($name === 'imagen')
                            <div id="preview-wrapper" class="position-relative mt-3" style="display: none;">
                                <button type="button" id="clear-preview" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Cerrar" onclick="clearPreview()"></button>
                                <img id="preview" src="#" alt="Vista previa" class="img-fluid border rounded p-1" style="max-height: 300px;">
                            </div>
                        @endif

                    {{-- Campo INPUT normal --}}
                    @else
                        <input
                            type="{{ $type }}"
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-control"
                            placeholder="{{ $placeholder }}"
                            value="{{ $value }}"
                        >
                    @endif

                    {{-- Muestra error específico del campo --}}
                    @error($name)
                        <div class="text-danger mt-1 small">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach

            <div class="d-flex mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ $textoBoton ?? 'Guardar' }}
                </button>
                <a href="{{ $rutaVolver }}" class="btn btn-outline-secondary ms-2">
                    Volver
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImagen(event) {
    const input = event.target;
    const wrapper = document.getElementById('preview-wrapper');
    const preview = document.getElementById('preview');
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            wrapper.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function clearPreview() {
    const input = document.getElementById('imagen');
    const preview = document.getElementById('preview');
    const wrapper = document.getElementById('preview-wrapper');

    input.value = '';
    preview.src = '#';
    wrapper.style.display = 'none';
}
</script>
@endpush
