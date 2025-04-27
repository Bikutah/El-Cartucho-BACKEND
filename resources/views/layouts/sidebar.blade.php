<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-gamepad"></i>
        </div>
        <div class="sidebar-brand-text mx-3">El Cartucho</div>
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
    <div class="sidebar-heading">
        Administración
    </div>
    <!-- Nav Item - Categorías -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('categorias.index') }}">
            <i class="fas fa-fw fa-tags"></i>
            <span>Categorías</span></a>
    </li>
    <!-- Nav Item - Productos -->
    <li class="nav-item">
        <a class="nav-link" href="">
            <i class="fas fa-fw fa-box"></i>
            <span>Productos</span></a>
    </li>
    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">
</ul>
<!-- End of Sidebar -->
