<!-- Sidebar fijo en pantallas grandes -->
<aside id="sidebar" class="sidebar d-none d-lg-block sidebar-expanded text-white">
    <a class="d-flex align-items-center justify-content-center py-3 px-2 text-decoration-none text-white" href="{{ route('home') }}">
        <div class="sidebar-brand-icon me-2">
            <img src="{{ asset('assets/img/caballero.webp') }}" alt="Favicon" style="width:40px; height:40px;">
        </div>
        <div class="sidebar-brand-text fw-bold">El Cartucho</div>
    </a>

    <hr class="sidebar-divider my-2">

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link text-white" href="/">
                <i class="fas fa-fw fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>

        <hr class="sidebar-divider my-2">

        <div class="sidebar-heading px-3 text-uppercase small fw-bold">
            Administración
        </div>

        <li class="nav-item">
            <a class="nav-link text-white" href="{{ route('categorias.index') }}">
                <i class="fas fa-fw fa-tags me-2"></i>
                Categorías
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link text-white" href="{{ route('subcategorias.index') }}">
                <i class="fas fa-fw fa-tags me-2"></i>
                Subcategorías
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link text-white" href="{{ route('productos.index') }}">
                <i class="fas fa-fw fa-box me-2"></i>
                Productos
            </a>
        </li>
    </ul>
</aside>

<!-- Offcanvas para móviles -->
<div class="offcanvas offcanvas-start offcanvas-sidebar-custom" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel">El Cartucho</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-flex align-items-center mb-4">
            <div class="profile-circle me-2"></div>
            <span>{{ auth()->user()->name }}</span>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="/">
                    <i class="fas fa-fw fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>

            <hr class="sidebar-divider my-2">

            <div class="sidebar-heading px-3 text-uppercase small fw-bold text-white">
                Administración
            </div>

            <li class="nav-item">
                <a class="nav-link text-white" href="{{ route('categorias.index') }}">
                    <i class="fas fa-fw fa-tags me-2"></i> Categorías
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="{{ route('subcategorias.index') }}">
                    <i class="fas fa-fw fa-tags me-2"></i> Subcategorías
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="{{ route('productos.index') }}">
                    <i class="fas fa-fw fa-box me-2"></i> Productos
                </a>
            </li>

            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Logout form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
