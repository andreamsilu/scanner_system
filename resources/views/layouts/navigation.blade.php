<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" height="40">
            <span class="ms-2 fw-bold">Event Tickets</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Right Side Menu -->
            <ul class="navbar-nav ms-auto">
                @auth
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="badge bg-danger rounded-pill">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            @forelse(auth()->user()->unreadNotifications as $notification)
                                <a class="dropdown-item" href="#">
                                    {{ $notification->data['message'] }}
                                </a>
                            @empty
                                <div class="dropdown-item text-muted">No new notifications</div>
                            @endforelse
                        </div>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            {{ auth()->user()->name }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person me-1"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar for larger screens -->
<div class="d-none d-lg-block position-fixed top-0 start-0 h-100 bg-white shadow-sm" style="width: 250px; padding-top: 56px;">
    <div class="p-3">
        <ul class="nav flex-column">
            @auth
                <!-- Dashboard -->
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active bg-light' : '' }}" 
                       href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                {{-- Admin Menu --}}
                @if(auth()->user()->isAdmin())
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('events.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('events.index') }}">
                            <i class="bi bi-calendar-event me-2"></i> Events
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('reports.index') }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('users.index') }}">
                            <i class="bi bi-people me-2"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('settings.index') }}">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    </li>
                @endif

                {{-- Scanner Menu: show for both scanner and admin --}}
                @if(auth()->user()->isScanner() || auth()->user()->isAdmin())
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.events.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('scanner.events.index') }}">
                            <i class="bi bi-upc-scan me-2"></i> Scan Tickets
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.history.*') ? 'active bg-light' : '' }}" 
                           href="{{ route('scanner.history') }}">
                            <i class="bi bi-clock-history me-2"></i> Scan History
                        </a>
                    </li>
                @endif
            @endauth
        </ul>
    </div>
</div>

<!-- Add custom styles -->
<style>
    .navbar {
        z-index: 1030;
    }
    
    .nav-link {
        color: #4a5568;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
    }
    
    .nav-link:hover {
        background-color: #f7fafc;
    }
    
    .nav-link.active {
        color: #2563eb;
        background-color: #eff6ff;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem;
    }
</style>
