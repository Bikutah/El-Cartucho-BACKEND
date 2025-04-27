@extends('layouts.app')

@section('title', $titulo ?? 'Formulario')

@section('content')

<h1 class="h3 mb-4 text-gray-800">{{ $titulo ?? 'Formulario' }}</h1>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ $action }}" method="POST">
            @csrf

            @if (isset($method) && $method !== 'POST')
                @method($method)
            @endif

            @foreach ($campos as $campo)
                <div class="mb-3">
                    <label for="{{ $campo['name'] }}" class="form-label">{{ $campo['label'] }}</label>
                    <input
                        type="{{ $campo['type'] ?? 'text' }}"
                        id="{{ $campo['name'] }}"
                        name="{{ $campo['name'] }}"
                        class="form-control"
                        placeholder="{{ $campo['placeholder'] ?? '' }}"
                        value="{{ old($campo['name'], $campo['value'] ?? '') }}"
                        @if (!empty($campo['required'])) required @endif
                    >
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">{{ $textoBoton ?? 'Guardar' }}</button>
            <a href="{{ $rutaVolver }}" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</div>

@endsection
