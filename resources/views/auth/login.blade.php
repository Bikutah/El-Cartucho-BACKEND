@extends('layouts.base')

@section('content-base')

<div class="d-flex justify-content-center align-items-center vh-100 bg-gradient">
    <div class="card shadow p-4 card-login-custom">
        <h2 class="text-center mb-4 text-white">Iniciar Sesión</h2>


        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label text-white">Usuario</label>
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
                <label for="password" class="form-label text-white">Contraseña</label>
                <div class="input-group">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control @error('password') is-invalid @enderror"
                        required
                    >
                    <button type="button" class="btn btn-light rounded-end" id="togglePassword">
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
                <button type="submit" class="btn btn-secondary w-100">Ingresar</button>
            </div>
        </form>
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
</script>
@endpush

@endsection
