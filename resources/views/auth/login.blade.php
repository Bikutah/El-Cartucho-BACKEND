@extends('layouts.base')

@section('content-base')

<style>
    .bg-gradient {
        margin: 0;
        padding: 0;
        background-color: #0a0a1a;
        position: relative;
        overflow: hidden;
    }
    
    .archway-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background-image: url('/assets/img/background-image.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        z-index: 1;
    }


    .card-login-custom {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
        max-width: 320px;
        z-index: 10;
        background-color: rgba(16, 12, 36, 0.6) !important; /* Fondo morado transparente */
        border: none !important;
        padding: 20px;
    }
    
    h2 {
        color: #ff9d4d !important; /* Color naranja similar a las antorchas */
        text-shadow: 0 0 10px rgba(255, 157, 77, 0.5) !important;
        letter-spacing: 1px !important;
        font-size: 1.5rem !important;
    }
    
    .form-label {
        color: #c4a7ff !important; /* Color lavanda claro */
        display: flex !important;
        align-items: center !important;
        font-size: 0.9rem !important;
    }
    
    .form-label::before {
        content: '';
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 8px;
        background-size: contain;
        background-repeat: no-repeat;
    }
    
    label[for="name"]::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='%23c4a7ff'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E");
    }
    
    label[for="password"]::before {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='%23c4a7ff'%3E%3Cpath d='M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z'/%3E%3C/svg%3E");
    }
    
    .form-control {
        background-color: rgba(30, 20, 60, 0.7) !important;
        border: 1px solid #3d2b5f !important;
        border-radius: 0 !important;
        color: #ffffff !important;
        padding: 8px !important;
        height: 40px !important;
        font-size: 0.9rem !important;
    }
    
    .form-control:focus {
        background-color: rgba(40, 30, 80, 0.8) !important;
        border-color: #6e4db3 !important;
        box-shadow: 0 0 8px rgba(110, 77, 179, 0.5) !important;
        color: #ffffff !important;
    }
    
    .btn-secondary {
        background: linear-gradient(to bottom, #ff9d4d, #ff7e1f) !important;
        border: 1px solid #ff7e1f !important;
        color: #1a1040 !important;
        font-weight: bold !important;
        padding: 8px 20px !important;
        transition: all 0.3s !important;
        border-radius: 0 !important;
        height: 40px !important;
        font-size: 0.9rem !important;
    }
    
    .btn-secondary:hover {
        background: linear-gradient(to bottom, #ffb06a, #ff9d4d) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(255, 157, 77, 0.3) !important;
    }
    
    .btn-light {
        background-color: rgba(30, 20, 60, 0.7) !important;
        border: 1px solid #3d2b5f !important;
        color: #c4a7ff !important;
        height: 40px !important;
    }
    
    .btn-light:hover {
        background-color: rgba(40, 30, 80, 0.8) !important;
        color: #ffffff !important;
    }
    
    .btn-light i {
        color: #c4a7ff !important;
    }
    
    /* Estilo para mensajes de error */
    .invalid-feedback {
        color: #ff6a6a !important;
        margin-top: 5px !important;
        font-size: 0.8rem !important;
    }
    
    /* Ajuste para que el formulario encaje perfectamente en la puerta */
    .form-container {
        position: relative;
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    /* Ajustes responsive */
    @media (max-width: 768px) {
        .archway-container {
            background-size: cover;
        }
        
        .card-login-custom {
            max-width: 280px;
            padding: 15px;
        }
        
        h2 {
            font-size: 1.3rem !important;
        }
    }
</style>

<div class="bg-gradient">
    <div class="archway-container"></div>
    
    <div class="form-container">
        <div class="card-login-custom">
            <h2 class="text-center mb-3 fw-bold">INICIAR SESIÓN</h2>

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Usuario</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        required 
                        autofocus
                    >
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control @error('password') is-invalid @enderror"
                            required
                        >
                        <button type="button" class="btn btn-light" id="togglePassword">
                            <i class="fas fa-eye" id="iconPassword"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-flex justify-content-center align-items-center">
                    <button type="submit" class="btn btn-secondary w-100">INGRESAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para el ojito -->
@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('iconPassword');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Ajustar tamaño del formulario para que encaje en la puerta
    function adjustFormSize() {
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        
        // Ajustar el tamaño del formulario según el tamaño de la ventana
        // para que siempre encaje en la puerta de la imagen
        if (windowWidth < 768) {
            // En móviles, hacer el formulario más pequeño
            document.querySelector('.card-login-custom').style.maxWidth = '280px';
        } else {
            // En pantallas más grandes, ajustar al tamaño de la puerta
            document.querySelector('.card-login-custom').style.maxWidth = '320px';
        }
    }
    
    // Ejecutar al cargar y al cambiar el tamaño de la ventana
    window.addEventListener('load', adjustFormSize);
    window.addEventListener('resize', adjustFormSize);
</script>
@endpush

@endsection