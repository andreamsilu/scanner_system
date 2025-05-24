<x-app-layout>
    <x-page-header 
        title="Scanner Dashboard"
        :breadcrumbs="['Scanner Dashboard' => null]"
    />

    <!-- Currently Selected Event -->
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
                            <i class="bi bi-upc-scan me-1"></i>
                            Continue Scanning
                        </a>
                        <form action="{{ route('scanner.select-event', ['event' => $selectedEvent->id]) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('Are you sure you want to change the selected event?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="bi bi-x-circle me-1"></i>
                                Change Event
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

    <!-- Today's Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
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

        <div class="col-md-4">
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

        <div class="col-md-4">
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
    </div>

    <!-- Assigned Events Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Your Assigned Events</h5>
                        @if(session('selected_event_id'))
                            <span class="badge bg-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Event Selected
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($assigned_events->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No assigned events found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-muted">Event</th>
                                        <th class="text-muted">Date</th>
                                        <th class="text-muted">Attendees</th>
                                        <th class="text-muted">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assigned_events as $event)
                                        <tr class="{{ session('selected_event_id') == $event->id ? 'table-primary' : '' }}">
                                            <td>
                                                <div class="fw-medium">
                                                    {{ $event->name }}
                                                    @if(session('selected_event_id') == $event->id)
                                                        <span class="badge bg-primary ms-2">Selected</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small">{{ $event->location }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $event->start_date->format('M d, Y') }}</div>
                                                <div class="text-muted small">{{ $event->start_date->format('h:i A') }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $event->attendees->count() }}</div>
                                                <div class="text-muted small">Total Attendees</div>
                                            </td>
                                            <td>
                                                @if(session('selected_event_id') == $event->id)
                                                    <a href="{{ route('scanner.scan.index', ['event' => $event->id]) }}" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="bi bi-upc-scan me-1"></i>
                                                        Continue Scanning
                                                    </a>
                                                @else
                                                    <a href="{{ route('scanner.select-event', $event) }}" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Select Event
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                                            <td>{{ $scan->created_at->diffForHumans() }}</td>
                                            <td>{{ $scan->ticket->event->name ?? 'N/A' }}</td>
                                            <td>{{ $scan->ticket->attendee->name ?? 'Invalid Ticket' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 
                                                    ($scan->status === 'duplicate' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($scan->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 