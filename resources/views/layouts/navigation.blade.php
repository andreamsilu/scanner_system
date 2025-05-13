@auth
    <nav class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                @if(auth()->user()->role === 'admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}" href="{{ route('events.index') }}">
                            <i class="fas fa-calendar-alt"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('attendees.*') ? 'active' : '' }}" href="{{ route('attendees.index') }}">
                            <i class="fas fa-users"></i> Attendees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('event-cards.*') ? 'active' : '' }}" href="{{ route('event-cards.index') }}">
                            <i class="fas fa-id-card"></i> Event Cards
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fas fa-user-cog"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
                            <i class="fas fa-history"></i> Activity Logs
                        </a>
                    </li>
                @elseif(auth()->user()->role === 'scanner')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('scanner.dashboard') ? 'active' : '' }}" href="{{ route('scanner.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Scanner Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('scanner.history') ? 'active' : '' }}" href="{{ route('scanner.history') }}">
                            <i class="fas fa-history"></i> Scan History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('scanner.export') ? 'active' : '' }}" href="{{ route('scanner.export') }}">
                            <i class="fas fa-file-export"></i> Export Data
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endauth

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