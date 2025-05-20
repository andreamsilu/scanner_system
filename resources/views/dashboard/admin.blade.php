<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="display-6 fw-bold text-dark mb-0">Admin Dashboard</h2>
            <div class="text-muted">
                Welcome, {{ Auth::user()->name }}
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-calendar-event fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Events</h6>
                            <h3 class="card-title mb-0">{{ $stats['total_events'] }}</h3>
                            <small class="text-success">{{ $stats['active_events'] }} Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-people fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Attendees</h6>
                            <h3 class="card-title mb-0">{{ $stats['total_attendees'] }}</h3>
                            <small class="text-muted">Registered</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-person-badge fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Active Scanners</h6>
                            <h3 class="card-title mb-0">{{ $stats['active_scanners'] }}</h3>
                            <small class="text-muted">Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-upc-scan fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Today's Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['today_scans'] }}</h3>
                            <small class="text-muted">Total: {{ $stats['total_scans'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Activity -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                    <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-file-earmark-text me-2"></i>View Reports
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Scanner</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_activity as $activity)
                                    <tr>
                                        <td>{{ $activity['time'] }}</td>
                                        <td>{{ $activity['event'] }}</td>
                                        <td>{{ $activity['scanner'] }}</td>
                                        <td>{{ $activity['attendee'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $activity['status_class'] }}">
                                                {{ ucfirst($activity['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fa-2x mb-2"></i>
                                                <p class="mb-0">No recent activity</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Events & Scanner Performance -->
        <div class="col-md-4">
            <!-- Upcoming Events -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Upcoming Events</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($upcoming_events as $event)
                            <a href="{{ route('events.show', $event) }}" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $event->name }}</h6>
                                    <small class="text-muted">
                                        {{ $event->start_date->format('M d') }}
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $event->venue }}
                                </small>
                            </a>
                        @empty
                            <div class="list-group-item text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-calendar-x fa-2x mb-2"></i>
                                    <p class="mb-0">No upcoming events</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Scanner Performance -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Top Scanners Today</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($scanner_performance as $scanner)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $scanner->name }}</h6>
                                        <small class="text-muted">{{ $scanner->email }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        {{ $scanner->scan_logs_count }} scans
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-person-x fa-2x mb-2"></i>
                                    <p class="mb-0">No scanner activity today</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
    @endpush
</x-app-layout> 