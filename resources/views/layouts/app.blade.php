<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Event Ticketing System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

        <!-- Custom CSS -->
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                background-color: #f8fafc;
            }

            .content-wrapper {
                min-height: calc(100vh - 56px);
                padding: 2rem;
            }

            @media (min-width: 992px) {
                .content-wrapper {
                    margin-left: 250px;
                }
            }

            /* Ticket-specific styles */
            .ticket-card {
                border: 2px solid #e2e8f0;
                border-radius: 0.5rem;
                transition: all 0.3s ease;
            }

            .ticket-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .ticket-status {
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .ticket-status.valid {
                background-color: #dcfce7;
                color: #166534;
            }

            .ticket-status.used {
                background-color: #fee2e2;
                color: #991b1b;
            }

            .ticket-status.expired {
                background-color: #f3f4f6;
                color: #4b5563;
            }

            .scan-area {
                border: 2px dashed #e2e8f0;
                border-radius: 0.5rem;
                padding: 2rem;
                text-align: center;
                background-color: white;
                transition: all 0.3s ease;
            }

            .scan-area:hover {
                border-color: #2563eb;
                background-color: #eff6ff;
            }
        </style>

        @stack('styles')
    </head>
    <body>
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Content -->
            <main class="content-wrapper">
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

                @yield('content')
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
