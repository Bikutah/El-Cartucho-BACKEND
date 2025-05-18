<!-- Sidebar para pantallas grandes -->
<aside id="sidebar" class="sidebar d-none d-lg-block">
    <div class="py-4 px-3 text-center">
        <a class="d-flex align-items-center justify-content-center text-decoration-none sidebar-brand" href="{{ route('home') }}">
            <div class="sidebar-brand-icon me-1">
                <img src="{{ asset('assets/img/caballero.webp') }}" alt="Logo" class="img-fluid" style="width:36px; height:36px;">
            </div>
            <div class="sidebar-brand-text fw-bold">El Cartucho</div>
        </a>
    </div>

    <hr class="sidebar-divider my-2">

    <ul class="nav flex-column mb-4 px-2">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center {{ request()->is('/') ? 'active' : '' }}" href="/">
                <i class="fas fa-gauge-high me-2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <div class="sidebar-heading">
            Administración
        </div>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center {{ request()->is('categorias*') ? 'active' : '' }}" href="{{ route('categorias.index') }}">
                <i class="fas fa-tags me-2"></i>
                <span>Categorías</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center {{ request()->is('subcategorias*') ? 'active' : '' }}" href="{{ route('subcategorias.index') }}">
                <i class="fas fa-tag me-2"></i>
                <span>Subcategorías</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center {{ request()->is('productos*') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                <i class="fas fa-box me-2"></i>
                <span>Productos</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Offcanvas para móviles -->
<div class="offcanvas offcanvas-start offcanvas-sidebar-custom" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center">
            <img src="{{ asset('assets/img/caballero.webp') }}" alt="Logo" class="img-fluid me-2" style="width:28px; height:28px;">
            <h5 class="offcanvas-title m-0 text-white fw-bold" id="offcanvasSidebarLabel">El Cartucho</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="d-flex align-items-center p-3" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="profile-circle me-2"></div>
            <div>
                <span class="me-2 d-md-inline-block badge" style="background-color: var(--color-indigo-light)"> Usuario: {{ auth()->user()->name }}</span>
            </div>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->is('/') ? 'active' : '' }}" href="/">
                    <i class="fas fa-gauge-high me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <div class="sidebar-heading">
                Administración
            </div>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->is('categorias*') ? 'active' : '' }}" href="{{ route('categorias.index') }}">
                    <i class="fas fa-tags me-2"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->is('subcategorias*') ? 'active' : '' }}" href="{{ route('subcategorias.index') }}">
                    <i class="fas fa-tag me-2"></i>
                    <span>Subcategorías</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->is('productos*') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                    <i class="fas fa-box me-2"></i>
                    <span>Productos</span>
                </a>
            </li>
        </ul>
        <div class="mt-4 px-3">
            <a class="nav-link d-flex align-items-center text-danger" href="#" 
               onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </div>
</div>

<!-- Formularios de logout -->
<form id="logout-form-sidebar" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
<form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>