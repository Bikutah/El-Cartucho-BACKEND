@extends('layouts.base')

@section('title', 'Página no encontrada')

@section('content-base')
<div class="error-page">
    <div class="archway-container"></div>
    
    <div class="error-container">
        <div class="card-error-custom">
            <div class="text-center">
                <div class="souls-lost">
                    <span class="souls-count">404</span>
                    <span class="souls-text">ALMAS PERDIDAS</span>
                </div>
                
                <div class="you-died">
                    <span>YOU DIED</span>
                </div>
                
                <h2 class="error-title mb-3">Camino Extraviado</h2>
                
                <p class="error-message mb-4">
                    El sendero que buscas se ha desvanecido en la oscuridad. Quizás en otra vida, o en otro ciclo...
                </p>
                
                <div class="bonfire-container">
                    <div class="bonfire-flames"></div>
                    <div class="bonfire-base"></div>
                </div>
                <div class="error-actions">
                    <a href="/" class="btn-home">
                        <i class="fas fa-home"></i>
                        Volver al inicio
                    </a>
                    
                    <button onclick="history.back()" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                         Regresar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection