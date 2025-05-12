@extends('layouts.app')

@section('title', $titulo ?? 'Formulario')

@section('content')

<h1 class="h3 mb-4 text-gray-800 font-weight-bold">{{ $titulo ?? 'Formulario' }}</h1>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if (isset($method) && $method !== 'POST')
                @method($method)
            @endif

            @foreach ($campos as $campo)
                <div class="mb-3">
                    <label for="{{ $campo['name'] }}" class="form-label">{{ $campo['label'] }}</label>

                    @if (($campo['type'] ?? 'text') === 'select')
                        <select
                            id="{{ $campo['name'] }}"
                            name="{{ $campo['name'] }}"
                            class="form-control"
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
                            <label for="imagen" class="btn btn-primary">
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

                            <div class="mt-3">
                                <img id="preview" src="#" alt="Vista previa" style="max-width: 100%; max-height: 300px; display: none;">
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
            <button type="submit" class="btn btn-primary">{{ $textoBoton ?? 'Guardar' }}</button>
            <a href="{{ $rutaVolver }}" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</div>

@endsection
