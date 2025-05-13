<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (session()->has('success'))
        <meta name="flash-message" content="{{ session('success') }}">
        <meta name="flash-type" content="success">
    @endif

    @if (session()->has('error'))
        <meta name="flash-message" content="{{ session('error') }}">
        <meta name="flash-type" content="error">
    @endif

    @if (session()->has('warning'))
        <meta name="flash-message" content="{{ session('warning') }}">
        <meta name="flash-type" content="warning">
    @endif

    @if (session()->has('info'))
        <meta name="flash-message" content="{{ session('info') }}">
        <meta name="flash-type" content="info">
    @endif

    @if (session()->has('page_expired'))
        <meta name="page-expired" content="true">
    @endif

    <title>{{ config('app.name', 'Event MG') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    <style>
        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Layout Components */
        .wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            min-width: 250px;
            background-color: #fff;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            z-index: 1040;
            overflow-y: auto;
        }

        .content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 1.5rem;
            padding-top: 75px;
            background-color: #f8f9fa;
        }

        /* Top Navigation */
        .navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 1030;
            height: 60px;
            padding: 0.5rem 1rem;
        }

        /* Navigation Styles */
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover {
            color: #0d6efd;
            background-color: #f8f9fa;
        }

        .sidebar .nav-link.active {
            color: #0d6efd;
            background-color: #e9ecef;
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }

        /* User Profile Styles */
        .user-profile {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
        }

        .user-profile img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }

        .user-profile .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-profile .user-role {
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Dropdown Styles */
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            color: #333;
            text-decoration: none;
        }

        .user-dropdown .dropdown-toggle img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .user-dropdown .dropdown-menu {
            min-width: 200px;
            padding: 0.5rem 0;
            margin: 0;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .user-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
        }

        .user-dropdown .dropdown-item i {
            width: 20px;
            margin-right: 0.5rem;
            color: #6c757d;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            .navbar {
                left: 0;
            }
            .navbar.active {
                left: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-sticky">
                <!-- App Logo -->
                <div class="text-center py-3">
                    <h4 class="mb-0">{{ config('app.name', 'Event MG') }}</h4>
                </div>

                <!-- Navigation -->
                @include('layouts.navigation')
            </div>
        </nav>

        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <h5 class="mb-0 d-none d-lg-block">@yield('title', 'Dashboard')</h5>
                </div>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                    <span class="text-primary fw-bold">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
                                        <i class="fas fa-user me-2 text-muted"></i>
                                        Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center">
                                            <i class="fas fa-sign-out-alt me-2 text-muted"></i>
                                            Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- Flash Message Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessage = document.querySelector('meta[name="flash-message"]');
            const flashType = document.querySelector('meta[name="flash-type"]');
            
            if (flashMessage && flashType) {
                const message = flashMessage.getAttribute('content');
                const type = flashType.getAttribute('content');
                
                // Create and show toast notification
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
        });
    </script>

    @stack('scripts')
</body>
</html> 