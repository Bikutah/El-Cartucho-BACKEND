@extends('layouts.base')
@section('content-base')
<body id="page-top">
    <div id="wrapper">
        @include('layouts.sidebar')
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
        <!-- Main Content -->
            <div id="content" class="d-flex flex-column flex-grow-1">
                @include('layouts.navbar')
                <!-- Aquí se expande al espacio disponible -->
                <div class="container-fluid flex-grow-1 mt-4">
                    @yield('content')
                </div>
            </div>
        </div>   
    </div>
</body>
@endsection