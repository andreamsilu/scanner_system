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

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 56px; /* Height of navbar */
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
            width: 250px;
            transition: all 0.3s;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
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

        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            padding: 1rem;
            color: #6c757d;
            font-weight: 600;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
        }

        /* Navbar Toggle Button */
        .navbar-toggler-sidebar {
            display: none;
        }

        @media (max-width: 768px) {
            .navbar-toggler-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        @include('layouts.navigation')
        
        <div class="d-flex">
            <!-- Sidebar -->
            <nav class="sidebar">
                <div class="sidebar-sticky">
                    <div class="sidebar-heading">Main</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>
                        </li>
                        @if(auth()->user()->isAdmin())
                            <div class="sidebar-heading">Administration</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users*') ? 'active' : '' }}" href="{{ route('users') }}">
                                    <i class="fas fa-users"></i>
                                    Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('events*') ? 'active' : '' }}" href="{{ route('events.index') }}">
                                    <i class="fas fa-calendar-alt"></i>
                                    Events
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('event-cards*') ? 'active' : '' }}" href="{{ route('event-cards.index') }}">
                                    <i class="fas fa-id-card"></i>
                                    Event Cards
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('activity-logs*') ? 'active' : '' }}" href="{{ route('activity-logs') }}">
                                    <i class="fas fa-history"></i>
                                    Activity Logs
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.navbar-toggler-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('active');
                });
            }
        });
    </script>

    <!-- Feedback Modal -->
    <x-feedback-modal />
</body>
</html> 