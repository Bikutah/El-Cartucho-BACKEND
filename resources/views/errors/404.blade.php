@extends('layouts.base')

@section('title', 'Página no encontrada')

@section('content-base')
<div class="container text-center py-5">
    <h1 class="display-1 text-danger fw-bold">404</h1>
    <h2 class="mb-3 fw-semibold">Página no encontrada</h2>
    <p class="mb-4">Lo sentimos, la página que estás buscando no existe o ha sido movida.</p>
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="fas fa-home"></i> Volver al inicio
    </a>
</div>
@endsection
