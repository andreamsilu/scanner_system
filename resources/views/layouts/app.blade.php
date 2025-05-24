<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<<<<<<< HEAD
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

        <!-- Custom CSS -->
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                background-color: #f8f9fa;
                overflow-x: hidden;
            }

            /* Content wrapper styles */
            .content-wrapper {
                margin-left: 250px;
                padding-top: 56px;
                min-height: calc(100vh - 56px);
                transition: margin-left 0.3s ease-in-out;
                background-color: #f8f9fa;
            }

            @media (max-width: 991.98px) {
                .content-wrapper {
                    margin-left: 0;
                }
            }

            /* Page header styles */
            .page-header {
                background-color: #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,.05);
                margin-bottom: 1.5rem;
                padding: 1rem 0;
            }

            /* Card styles */
            .card {
                border: none;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            /* Alert styles */
            .alert {
                border: none;
                border-radius: 0.5rem;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }

            /* Table styles */
            .table {
                background-color: #fff;
                border-radius: 0.5rem;
                overflow: hidden;
            }

            .table thead th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
            }

            /* Button styles */
            .btn {
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: all 0.2s ease-in-out;
            }

            .btn-primary {
                background-color: #0d6efd;
                border-color: #0d6efd;
            }

            .btn-primary:hover {
                background-color: #0b5ed7;
                border-color: #0a58ca;
                transform: translateY(-1px);
            }

            /* Form control styles */
            .form-control {
                border-radius: 0.375rem;
                border: 1px solid #dee2e6;
                padding: 0.5rem 0.75rem;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .form-control:focus {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/js/app.js'])
    </head>
    <body>
        <div class="min-vh-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="page-header">
                    <div class="container-fluid">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="content-wrapper">
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

                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

        <!-- Initialize DataTables -->
        <script>
            $(document).ready(function() {
                $('.datatable').DataTable({
                    responsive: true,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    }
                });
            });
        </script>

        @stack('scripts')
    </body>
</html>
=======
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Event Tickets') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
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
            padding: 1.5rem 0;
        }

        /* Ensure alerts don't overlap with fixed navbar */
        .alert {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Main Content -->
    <div class="content-wrapper">
        <main>
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

                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html> 
>>>>>>> 85970daf80a85c2d0fa946cca0006fedd52363c1
