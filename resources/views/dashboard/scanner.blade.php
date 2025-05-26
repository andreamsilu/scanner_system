<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">Scanner Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
            </div>
            <div>
                <a href="{{ route('scanner.history') }}" class="btn btn-outline-primary">
                    <i class="bi bi-clock-history me-2"></i>View History
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Event Selection Alert -->
    @if(session('selected_event_id'))
        @php
            $selectedEvent = \App\Models\Event::find(session('selected_event_id'));
        @endphp
        @if($selectedEvent)
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                    <div class="flex-grow-1">
                        <strong>Currently Selected Event:</strong> {{ $selectedEvent->name }}
                        <div class="small text-muted">
                            {{ $selectedEvent->start_date->format('M d, Y h:i A') }} at {{ $selectedEvent->location }}
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('scanner.scan.index', ['event' => $selectedEvent->id]) }}" 
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-upc-scan me-1"></i>Continue Scanning
                        </a>
                        <form action="{{ route('scanner.select-event', ['event' => $selectedEvent->id]) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('Are you sure you want to change the selected event?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="bi bi-x-circle me-1"></i>Change Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                <div class="flex-grow-1">
                    <strong>No Event Selected</strong>
                    <div class="small">Please select an event from the list below to start scanning.</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-upc-scan fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Today's Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['today_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Valid Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['valid_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Invalid Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['invalid_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-info bg-opacity-10 text-info">
                                <i class="bi bi-calendar-event fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Current Events</h6>
                            <h3 class="card-title mb-0">{{ $stats['current_events'] }}</h3>
                            <small class="text-muted">
                                {{ $stats['upcoming_events'] }} upcoming, {{ $stats['past_events'] }} past
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Events -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Your Assigned Events</h5>
                </div>
                <div class="card-body">
                    @if($assigned_events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Total Attendees</th>
                                        <th>Scanned</th>
                                        <th>Pending</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assigned_events as $event)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <span class="avatar-title rounded-circle bg-primary bg-opacity-10 text-primary">
                                                            {{ substr($event->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $event->name }}</h6>
                                                        <small class="text-muted">Assigned {{ $event->pivot->assigned_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>{{ $event->start_date->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $event->start_date->format('h:i A') }}</small>
                                            </td>
                                            <td>{{ $event->location }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match($event->status) {
                                                        'active' => 'success',
                                                        'completed' => 'info',
                                                        'cancelled' => 'danger',
                                                        default => 'warning'
                                                    };
                                                    
                                                    $timelineClass = match(true) {
                                                        $event->start_date > now() => 'info',
                                                        $event->end_date < now() => 'secondary',
                                                        default => 'success'
                                                    };
                                                    
                                                    $timelineText = match(true) {
                                                        $event->start_date > now() => 'Upcoming',
                                                        $event->end_date < now() => 'Past',
                                                        default => 'Current'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ ucfirst($event->status) }}
                                                </span>
                                                <span class="badge bg-{{ $timelineClass }}">
                                                    {{ $timelineText }}
                                                </span>
                                            </td>
                                            <td>{{ $event->scan_stats['total_attendees'] }}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    {{ $event->scan_stats['scanned_count'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    {{ $event->scan_stats['pending_count'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($event->status === 'active' && $event->start_date <= now() && $event->end_date >= now())
                                                    <form action="{{ route('scanner.select-event', $event) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="bi bi-upc-scan me-1"></i>Start Scanning
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="bi bi-upc-scan me-1"></i>Not Available
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="avatar-circle bg-light mx-auto mb-3" style="width: 64px; height: 64px;">
                                <i class="bi bi-calendar-x fs-2 text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Events Assigned</h5>
                            <p class="text-muted mb-0">You haven't been assigned to any events yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($recent_activity->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Event</th>
                                        <th>Attendee</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_activity as $scan)
                                        <tr>
                                            <td>{{ $scan['time'] }}</td>
                                            <td>{{ $scan['event'] }}</td>
                                            <td>{{ $scan['attendee'] }}</td>
                                            <td>
                                                <span class="badge bg-{{ $scan['status_class'] }}">
                                                    {{ ucfirst($scan['status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="avatar-circle bg-light mx-auto mb-3" style="width: 64px; height: 64px;">
                                <i class="bi bi-clock-history fs-2 text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No Recent Activity</h5>
                            <p class="text-muted mb-0">Your scan history will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 