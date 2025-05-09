<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
        <div class="sidebar-brand-icon">
            <span><img src="{{ asset('assets/img/caballero.webp') }}" alt="Favicon" style="width:50px; height:50px;"></span>
        </div>
        <div class="sidebar-brand-text">El Cartucho</div>
    </a>
    <!-- Divider -->
    <hr class="sidebar-divider my-0">
    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="/">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    <!-- Divider -->
    <hr class="sidebar-divider">
    <!-- Heading -->
    <div class="sidebar-heading text-2xl font-bold">
        Administración
    </div>
    <!-- Nav Item - Categorías -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('categorias.index') }}">
            <i class="fas fa-fw fa-tags"></i>
            <span>Categorías</span></a>
    </li>
        <!-- Nav Item - Subcategorias -->
        <li class="nav-item">
        <a class="nav-link" href="{{ route('subcategorias.index') }}">
            <i class="fas fa-fw fa-tags"></i>
            <span>Subcategorías</span></a>
    </li>
    <!-- Nav Item - Productos -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('productos.index') }}">
            <i class="fas fa-fw fa-box"></i>
            <span>Productos</span></a>
    </li>
    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">
</ul>
<!-- End of Sidebar -->
