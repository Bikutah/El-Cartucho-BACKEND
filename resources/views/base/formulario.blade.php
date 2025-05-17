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
                    $required = !empty($campo['required']) ? 'required' : '';
                    $placeholder = $campo['placeholder'] ?? '';
                    $value = old($name, $campo['value'] ?? '');
                    $multiple = !empty($campo['multiple']);
                    $accept = $campo['accept'] ?? 'image/*';
                    $id = Str::slug($name, '_');
                @endphp

                <div class="mb-3">
                    <label for="{{ $id }}" class="form-label fw-semibold" style="color: var(--color-secundario);">
                        {{ $label }}
                        @if ($required) <span class="text-danger">*</span> @endif
                    </label>

                    @if ($type === 'select')
                        <select
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-select"
                            {{ $required }}
                        >
                            <option value="">{{ $placeholder ?: 'Seleccione una opción' }}</option>
                            @foreach (($campo['options'] ?? []) as $optionValue => $optionText)
                                <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
                                    {{ $optionText }}
                                </option>
                            @endforeach
                        </select>

                    @elseif ($type === 'textarea')
                        <textarea
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-control"
                            placeholder="{{ $placeholder }}"
                            rows="{{ $campo['rows'] ?? 4 }}"
                            cols="{{ $campo['cols'] ?? 50 }}"
                            {{ $required }}
                        >{{ $value }}</textarea>

                    @elseif ($type === 'file')
                        <input
                            type="file"
                            id="{{ $id }}"
                            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                            class="form-control"
                            accept="{{ $accept }}"
                            {{ $required }}
                            {{ $multiple ? 'multiple' : '' }}
                        >

                        {{-- Vista previa solo si es campo imagen simple --}}
                        @if ($name === 'imagen')
                            <div id="preview-wrapper" class="position-relative mt-3" style="display: none;">
                                <button type="button" id="clear-preview" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Cerrar" onclick="clearPreview()"></button>
                                <img id="preview" src="#" alt="Vista previa" class="img-fluid border rounded p-1" style="max-height: 300px;">
                            </div>
                        @endif

                    @else
                        <input
                            type="{{ $type }}"
                            id="{{ $id }}"
                            name="{{ $name }}"
                            class="form-control"
                            placeholder="{{ $placeholder }}"
                            value="{{ $value }}"
                            {{ $required }}
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
