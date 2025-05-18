<nav class="navbar navbar-expand py-0 px-4 custom-navbar">
    <!-- Botón hamburguesa para móviles -->
    <button class="btn btn-outline-secondary border-0 d-lg-none" type="button" data-bs-toggle="offcanvas" 
            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="d-none d-md-block fw-semibold">
        @php
            $segments = request()->segments();
            $count = count($segments);
        @endphp

        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>

            @foreach ($segments as $index => $segment)
                @php
                    $url = url(implode('/', array_slice($segments, 0, $index + 1)));
                    $isLast = $loop->last;

                    // Capitalizar el nombre y hacer que se vea más bonito
                    $name = match ($segment) {
                        'create' => 'Crear',
                        'edit' => 'Editar',
                        default => ucfirst(str_replace('-', ' ', $segment))
                    };

                    // Agregar el nombre de la entidad si es create/edit
                    if (in_array($segment, ['create', 'edit']) && $index >= 1) {
                        $entity = ucfirst(str_replace('-', ' ', $segments[$index - 1]));
                        $name .= " $entity";
                    }
                @endphp

                @if ($isLast)
                    <li class="breadcrumb-item active" aria-current="page">{{ $name }}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $name }}</a></li>
                @endif
            @endforeach
        </ol>
    </nav>
    <!-- Elementos a la derecha -->
    <ul class="navbar-nav ms-auto">
        <!-- Perfil de usuario -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
                <span class="me-2 d-none d-md-inline-block badge" style="background-color: var(--color-indigo-light-hover)"> Usuario: {{ auth()->user()->name }}</span>
                <div class="profile-circle"></div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                <li>
                    <a class="dropdown-item d-flex align-items-center text-danger" href="#"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw me-2"></i>
                        <span>Cerrar sesión</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<!-- Formulario de logout -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
