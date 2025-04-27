@extends('layouts.base')

@section('content-base')
<body id="page-top">

    <div id="wrapper">
            @include('layouts.sidebar')
    
            <!-- Content Wrapper -->
            <div id="content-wrapper" class="d-flex flex-column">
    
                <!-- Main Content -->
                <div id="content">
    
                    @include('layouts.navbar')

                    <div class="container-fluid mt-4">
                        @yield('content')
                    </div>
    
                </div>

    
            </div>

                
        </div>
    
    </body>

@endsection