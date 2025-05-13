<div class="nav flex-column">
    @auth
        @if(auth()->user()->hasRole('admin'))
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <!-- Events Section -->
            <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </a>

            <!-- Event Cards Section -->
            <a href="{{ route('event-cards.index') }}" class="nav-link {{ request()->routeIs('event-cards.*') ? 'active' : '' }}">
                <i class="fas fa-id-card"></i>
                <span>Event Cards</span>
            </a>

            <!-- Attendees Section -->
            <a href="{{ route('attendees.index') }}" class="nav-link {{ request()->routeIs('attendees.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Attendees</span>
            </a>

            <!-- Users Section -->
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i>
                <span>Users</span>
            </a>

            <!-- Activity Logs -->
            <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('scanner'))
            <!-- Scanner Section -->
            <a href="{{ route('scanner.dashboard') }}" class="nav-link {{ request()->routeIs('scanner.*') ? 'active' : '' }}">
                <i class="fas fa-qrcode"></i>
                <span>Scanner</span>
            </a>

            <!-- Scan History -->
            <a href="{{ route('scanner.history') }}" class="nav-link {{ request()->routeIs('scanner.history') ? 'active' : '' }}">
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>

            <!-- Export Data -->
            <a href="{{ route('scanner.export') }}" class="nav-link {{ request()->routeIs('scanner.export') ? 'active' : '' }}">
                <i class="fas fa-file-export"></i>
                <span>Export Data</span>
            </a>
        @endif
    @endauth
</div>

<style>
.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #333;
    transition: all 0.3s;
}

.nav-link:hover {
    color: #0d6efd;
    background-color: #f8f9fa;
}

.nav-link.active {
    color: #0d6efd;
    background-color: #e9ecef;
}

.nav-link i {
    width: 20px;
    margin-right: 0.5rem;
    text-align: center;
}

.nav-link span {
    font-weight: 500;
}
</style> 