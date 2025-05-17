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
            @if (isset($method) && $method !== 'POST')
                @method($method)
            @endif

            @foreach ($campos as $campo)
                <div class="mb-3">
                    <label for="{{ $campo['name'] }}" class="form-label fw-semibold" style="color: var(--color-secundario);">
                        {{ $campo['label'] }}
                        @if (!empty($campo['required'])) <span class="text-danger">*</span> @endif
                    </label>

                    @if (($campo['type'] ?? 'text') === 'select')
                        <select
                            id="{{ $campo['name'] }}"
                            name="{{ $campo['name'] }}"
                            class="form-select"
                            @if (!empty($campo['required'])) required @endif
                        >
                            <option value="">{{ $campo['placeholder'] ?? 'Seleccione una opción' }}</option>
                            @foreach ($campo['options'] as $value => $text)
                                <option value="{{ $value }}" {{ (old($campo['name'], $campo['value'] ?? null) == $value) ? 'selected' : '' }}>
                                    {{ $text }}
                                </option>
                            @endforeach
                        </select>

                    @elseif (($campo['type'] ?? 'text') === 'textarea')
                        <textarea
                            id="{{ $campo['name'] }}"
                            name="{{ $campo['name'] }}"
                            class="form-control"
                            placeholder="{{ $campo['placeholder'] ?? '' }}"
                            rows="{{ $campo['rows'] ?? 4 }}"
                            cols="{{ $campo['cols'] ?? 50 }}"
                            @if (!empty($campo['required'])) required @endif
                        >{{ old($campo['name'], $campo['value'] ?? '') }}</textarea>

                    @elseif (($campo['type'] ?? 'text') === 'file' || $campo['name'] === 'imagen')
                        <div class="mb-3">
                            <label for="imagen" class="btn btn-outline-primary">
                                Seleccionar imagen
                            </label>
                            <input
                                type="file"
                                id="imagen"
                                name="imagen"
                                accept="image/*"
                                class="d-none"
                                onchange="previewImagen(event)"
                                required
                            >

                            <div id="preview-wrapper" class="position-relative mt-3" style="display: none;">
                                <button type="button" id="clear-preview" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Cerrar" onclick="clearPreview()"></button>
                                <img id="preview" src="#" alt="Vista previa" class="img-fluid border rounded p-1" style="max-height: 300px;">
                            </div>
                        </div>

                    @else
                        <input
                            type="{{ $campo['type'] ?? 'text' }}"
                            id="{{ $campo['name'] }}"
                            name="{{ $campo['name'] }}"
                            class="form-control"
                            placeholder="{{ $campo['placeholder'] ?? '' }}"
                            value="{{ old($campo['name'], $campo['value'] ?? '') }}"
                            @if (!empty($campo['required'])) required @endif
                        >
                    @endif
                </div>
            @endforeach

            <div class="d-flex mt-4">
                <button type="submit" class="btn btn-primary">
                    {{ $textoBoton ?? 'Guardar' }}
                </button>
                <a href="{{ $rutaVolver }}" class="btn btn-outline-secondary">
                    Volver
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
