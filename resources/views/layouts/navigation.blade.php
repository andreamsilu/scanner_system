<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top d-lg-none">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" height="40">
            <span class="ms-2 fw-bold">Event Tickets</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="btn btn-link text-dark" type="button" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar-wrapper">
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar bg-white shadow-sm" id="sidebar">
        <!-- Logo -->
        <div class="px-3 py-4 border-bottom">
            <a class="d-flex align-items-center text-decoration-none" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" height="40">
                <span class="ms-2 fw-bold">Event Tickets</span>
            </a>
        </div>
        
        <!-- User Info -->
        @auth
            <div class="d-flex align-items-center mb-4 px-3 py-3 border-bottom">
                <div class="flex-shrink-0">
                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-person-circle fs-4"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                    <small class="text-muted">{{ auth()->user()->roles->first()->name ?? 'User' }}</small>
                </div>
            </div>

            <!-- Navigation Menu -->
            <ul class="nav flex-column px-2">
                <!-- Dashboard -->
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('scanner.dashboard') ? 'active' : '' }}" 
                       href="{{ route('scanner.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                @if(auth()->user()->isScanner() || auth()->user()->isAdmin())
                    <!-- Scanner Menu -->
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.events.*') ? 'active' : '' }}" 
                           href="{{ route('scanner.events.index') }}">
                            <i class="bi bi-calendar-event me-2"></i> My Events
                            @if(auth()->user()->isScanner())
                                <small class="text-muted d-block">(Assigned Events)</small>
                            @endif
                        </a>
                    </li>

                    @if(auth()->user()->isScanner())
                        @php
                            $assignedEvents = auth()->user()->events()
                                ->where('status', 'active')
                                ->where(function($query) {
                                    $query->where('start_date', '>=', now())
                                        ->orWhere('end_date', '>=', now());
                                })
                                ->get();
                        @endphp
                        
                        @if($assignedEvents->isNotEmpty())
                            <li class="nav-item mb-2">
                                <div class="nav-header text-muted small px-3 py-2">Active Events</div>
                                @foreach($assignedEvents as $event)
                                    <a class="nav-link ps-4 py-2 {{ request()->routeIs('scanner.events.show', $event) ? 'active' : '' }}" 
                                       href="{{ route('scanner.events.show', $event) }}">
                                        <i class="bi bi-circle-fill me-2 small"></i>
                                        {{ $event->name }}
                                        @if($event->isUpcoming())
                                            <span class="badge bg-success ms-2">Upcoming</span>
                                        @endif
                                    </a>
                                @endforeach
                            </li>
                        @endif
                    @endif

                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.validate') ? 'active' : '' }}" 
                           href="{{ route('scanner.validate') }}">
                            <i class="bi bi-key me-2"></i> Manual Validation
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.history.*') ? 'active' : '' }}" 
                           href="{{ route('scanner.history') }}">
                            <i class="bi bi-clock-history me-2"></i> Scan History
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ request()->routeIs('scanner.history.export') ? 'active' : '' }}" 
                           href="{{ route('scanner.history.export') }}">
                            <i class="bi bi-download me-2"></i> Export History
                        </a>
                    </li>

                    @if(auth()->user()->isAdmin())
                        <!-- Admin Menu -->
                        <li class="nav-item mb-2">
                            <div class="nav-header text-muted small px-3 py-2">Admin</div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}" 
                               href="{{ route('events.index') }}">
                                <i class="bi bi-calendar-event me-2"></i> All Events
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ request()->routeIs('events.*.attendees.*') ? 'active' : '' }}" 
                               href="{{ route('events.index') }}">
                                <i class="bi bi-people me-2"></i> Attendees
                                <small class="text-muted d-block">(Manage via Events)</small>
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" 
                               href="{{ route('users.index') }}">
                                <i class="bi bi-people me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" 
                               href="{{ route('reports.index') }}">
                                <i class="bi bi-file-earmark-text me-2"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" 
                               href="{{ route('settings.index') }}">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>
                    @endif
                @endif

                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" 
                       href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        @endauth
    </div>
</div>

<!-- Add custom styles -->
<style>
    /* Top navbar styles */
    .navbar {
        height: 60px;
        z-index: 1030;
    }

    /* Sidebar styles */
    .sidebar-wrapper {
        position: relative;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        z-index: 1020;
        transition: transform 0.3s ease-in-out;
        overflow-y: auto;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1010;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .sidebar-overlay.show {
        display: block;
        opacity: 1;
    }

    /* Nav link styles */
    .nav-link {
        color: #4a5568;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
        white-space: nowrap;
    }
    
    .nav-link:hover {
        background-color: #f7fafc;
    }
    
    .nav-link.active {
        color: #2563eb;
        background-color: #eff6ff;
    }

    /* User info styles */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Mobile styles */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            top: 60px; /* Account for fixed navbar */
            height: calc(100vh - 60px);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .content-wrapper {
            margin-left: 0 !important;
            padding-top: 60px; /* Account for fixed navbar */
        }

        body {
            padding-top: 60px; /* Account for fixed navbar */
        }
    }

    /* Desktop styles */
    @media (min-width: 992px) {
        .navbar {
            display: none;
        }

        .sidebar {
            transform: none;
        }

        .content-wrapper {
            margin-left: 250px;
        }
    }

    /* Scrollbar styles */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<!-- Add JavaScript for sidebar toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
        body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking a nav link on mobile
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                toggleSidebar();
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            body.style.overflow = '';
        }
    });
});
</script>
