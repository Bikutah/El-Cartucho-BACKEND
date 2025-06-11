@extends('layouts.base')

@section('content-base')

<body id="page-top">
    <div id="wrapper" class="d-flex">
        @include('layouts.sidebar')

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column min-vh-100 flex-grow-1">
            <!-- Main Content -->
            <div id="content" class="d-flex flex-column flex-grow-1">
                @include('layouts.navbar')

                <!-- Contenido principal -->
                <div class="container-fluid flex-grow-1 p-4">
                    <div id="alert-container">
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm" role="alert">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </div>
                                <div>
                                    {{ session('success') }}
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm" role="alert">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-exclamation-circle fa-lg"></i>
                                </div>
                                <div>
                                    {{ session('error') }}
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif
                    </div>
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
@endsection