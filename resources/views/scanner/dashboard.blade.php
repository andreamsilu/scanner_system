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
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-upc-scan fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted small">Today's Scans</h6>
                            <h3 class="card-title mb-0 h4">{{ $stats['today_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted small">Valid Scans</h6>
                            <h3 class="card-title mb-0 h4">{{ $stats['valid_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-circle bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted small">Invalid Scans</h6>
                            <h3 class="card-title mb-0 h4">{{ $stats['invalid_scans'] }}</h3>
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
                <div class="card-body p-0">
                    @if($assigned_events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event Name</th>
                                        <th class="d-none d-md-table-cell">Date</th>
                                        <th class="d-none d-lg-table-cell">Location</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Scanned</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assigned_events as $event)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $event->name }}</div>
                                                <div class="small text-muted d-md-none">
                                                    {{ $event->start_date->format('M d, Y h:i A') }}
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">{{ $event->start_date->format('M d, Y h:i A') }}</td>
                                            <td class="d-none d-lg-table-cell">{{ $event->location }}</td>
                                            <td class="text-center">{{ $event->attendees->count() }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $event->attendees->where('ticket.status', 'used')->count() }}</span>
                                                /
                                                <span class="badge bg-secondary">{{ $event->attendees->count() }}</span>
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('scanner.events.select', $event) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-upc-scan me-1"></i>Scan
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
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-3 mb-0">No assigned events found</p>
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
                <div class="card-body p-0">
                    @if($recent_activity->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th class="d-none d-md-table-cell">Event</th>
                                        <th>Attendee</th>
                                        <th class="text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_activity as $scan)
                                        <tr>
                                            <td>
                                                <div class="small">{{ $scan->created_at->diffForHumans() }}</div>
                                                <div class="small text-muted d-md-none">
                                                    {{ $scan->ticket->event->name ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">{{ $scan->ticket->event->name ?? 'N/A' }}</td>
                                            <td>{{ $scan->ticket->attendee->name ?? 'Invalid Ticket' }}</td>
                                            <td class="text-end">
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
                            <i class="bi bi-clock-history text-muted" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-3 mb-0">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            .avatar-circle {
                width: 40px;
                height: 40px;
            }
            .avatar-circle i {
                font-size: 1.25rem !important;
            }
            .table th, .table td {
                padding: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .card-header {
                padding: 0.75rem 1rem;
            }
            .card-body {
                padding: 0.75rem;
            }
            .avatar-circle {
                width: 36px;
                height: 36px;
            }
            .avatar-circle i {
                font-size: 1rem !important;
            }
            .table th, .table td {
                padding: 0.5rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
    @endpush
</x-app-layout> 