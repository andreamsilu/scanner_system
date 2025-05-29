<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="display-6 fw-bold text-dark mb-0">Scanner Dashboard</h2>
            <div class="text-muted">
                Welcome, {{ Auth::user()->name }}
            </div>
        </div>
    </x-slot>

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

    <!-- Active Events -->
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
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Total Attendees</th>
                                        <th>Scanned</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assigned_events as $event)
                                        <tr>
                                            <td>{{ $event->name }}</td>
                                            <td>{{ $event->start_date->format('M d, Y h:i A') }}</td>
                                            <td>{{ $event->location }}</td>
                                            <td>{{ $event->attendees->count() }}</td>
                                            <td>
                                                {{ $event->attendees->where('ticket.status', 'used')->count() }}
                                                /
                                                {{ $event->attendees->count() }}
                                            </td>
                                            <td>
                                                <form action="{{ route('scanner.events.select', $event) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-upc-scan me-1"></i> Scan
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No assigned events found</p>
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