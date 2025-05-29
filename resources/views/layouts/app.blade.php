<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        [x-cloak] { display: none !important; }
        body {
            min-height: 100vh;
            display: flex;
            background-color: #f8f9fa;
        }
        
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0;
                padding-top: 60px; /* Account for fixed navbar */
            }
        }
        
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Breadcrumb and header container */
        .breadcrumb-container,
        .header-container {
            position: relative;
            z-index: 2;
        }

        /* Content area */
        .content-area {
            flex: 1;
            padding: 1.5rem 0;
            position: relative;
            z-index: 1;
        }

        /* Ensure alerts don't overlap with fixed navbar */
        .alert {
            margin: 1rem 0;
            position: relative;
            z-index: 2;
        }

        /* Container padding adjustments */
        .container-fluid {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        @media (max-width: 767.98px) {
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <div class="main">
            <!-- Top Navigation -->
            @include('layouts.partials.navigation')

            <main class="content">
                <div class="container-fluid py-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                    @if(isset($slot))
                        {{ $slot }}
                    @else
                @yield('content')
                    @endif
            </div>
        </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    @stack('scripts')
</body>
</html> 