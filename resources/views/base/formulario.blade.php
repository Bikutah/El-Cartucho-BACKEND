@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('title', $titulo ?? 'Formulario')

@section('content')
    <h1 class="h3 mb-4 text-gray-800 font-weight-bold">{{ $titulo ?? 'Formulario' }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ $action }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @if (isset($method) && $method !== 'POST')
                    @method($method)
                @endif

                {{-- Campos dinámicos --}}
                @foreach ($campos as $campo)
                    @php
                        $inputId = 'input_' . Str::slug($campo['name'], '_');
                        $previewId = 'preview_' . Str::slug($campo['name'], '_');
                    @endphp

                    <div class="mb-3">
                        <label for="{{ $inputId }}" class="form-label">{{ $campo['label'] }}</label>

                        {{-- Select --}}
                        @if (($campo['type'] ?? 'text') === 'select')
                            <select
                                id="{{ $inputId }}"
                                name="{{ $campo['name'] }}"
                                class="form-control @error($campo['name']) is-invalid @enderror"
                                @if (!empty($campo['required'])) required @endif
                            >
                                <option value="">{{ $campo['placeholder'] ?? 'Seleccione una opción' }}</option>
                                @foreach ($campo['options'] as $value => $text)
                                    <option value="{{ $value }}" {{ old($campo['name'], $campo['value'] ?? null) == $value ? 'selected' : '' }}>
                                        {{ $text }}
                                    </option>
                                @endforeach
                            </select>

                        {{-- Textarea --}}
                        @elseif (($campo['type'] ?? 'text') === 'textarea')
                            <textarea
                                id="{{ $inputId }}"
                                name="{{ $campo['name'] }}"
                                class="form-control @error($campo['name']) is-invalid @enderror"
                                placeholder="{{ $campo['placeholder'] ?? '' }}"
                                rows="{{ $campo['rows'] ?? 4 }}"
                                cols="{{ $campo['cols'] ?? 50 }}"
                                @if (!empty($campo['required'])) required @endif
                            >{{ old($campo['name'], $campo['value'] ?? '') }}</textarea>

                        {{-- Campo de archivo --}}
                        @elseif (($campo['type'] ?? 'text') === 'file')
                            <input
                                type="file"
                                id="{{ $inputId }}"
                                name="imagenes[]" 
                                accept="image/*"
                                class="form-control @error($campo['name']) is-invalid @enderror"
                                @if (!empty($campo['required'])) required @endif
                                @if (!empty($campo['multiple'])) multiple @endif
                                onchange="previewMultipleImages(event, '{{ $previewId }}')"
                            />

                            {{-- Contenedor de previews --}}
                            <div class="mt-3 d-flex gap-2 flex-wrap" id="{{ $previewId }}"></div>

                        {{-- Input estándar --}}
                        @else
                            <input
                                type="{{ $campo['type'] ?? 'text' }}"
                                id="{{ $inputId }}"
                                name="{{ $campo['name'] }}"
                                class="form-control @error($campo['name']) is-invalid @enderror"
                                placeholder="{{ $campo['placeholder'] ?? '' }}"
                                value="{{ old($campo['name'], $campo['value'] ?? '') }}"
                                @if (!empty($campo['required'])) required @endif
                            >
                        @endif

                        {{-- Errores --}}
                        @error($campo['name'])
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        {{-- Errores por imagen individual --}}
                        @if (str_contains($campo['name'], 'imagenes'))
                            @error('imagenes')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                            @error('imagenes.*')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                @endforeach

                {{-- Botones --}}
                <button type="submit" class="btn btn-primary">{{ $textoBoton ?? 'Guardar' }}</button>
                <a href="{{ $rutaVolver }}" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
@endsection