@extends('layouts.base')

@section('content-base')

<div class="login-page bg-gradient">
    <div class="archway-container"></div>
    <div class="form-container">
        <div class="card-login-custom">
            <h2 class="text-center mb-3 fw-bold">INICIAR SESIÓN</h2>

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label login-label">Usuario</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control login-input @error('name') is-invalid @enderror"
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
                    <label for="password" class="form-label login-label">Contraseña</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control login-input @error('password') is-invalid @enderror"
                            required
                        >
                        <button type="button" class="btn btn-light login-eye-btn" id="togglePassword">
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
                    <button type="submit" class="btn btn-secondary w-100 login-submit-btn">INGRESAR</button>
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