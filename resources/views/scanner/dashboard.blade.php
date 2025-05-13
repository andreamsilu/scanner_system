@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="display-6 fw-bold text-dark mb-1">Scanner Dashboard</h2>
                    <p class="text-muted mb-0">Select an event to start scanning</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-qrcode text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Today's Scans</h6>
                            <h4 class="mb-0">{{ $today_scans }}</h4>
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
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Scans</h6>
                            <h4 class="mb-0">{{ $total_scans }}</h4>
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
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-calendar-alt text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Events</h6>
                            <h4 class="mb-0">{{ $active_events->count() }}</h4>
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
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-users text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Checked In Today</h6>
                            <h4 class="mb-0">{{ $checked_in_today }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Events -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Select Event to Scan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($active_events as $event)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $event->name }}</h5>
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-calendar me-2"></i>
                                            {{ $event->start_date->format('M d, Y') }}
                                        </p>
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            {{ $event->venue }}
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success">Active</span>
                                            <a href="{{ route('scanner.select-event', $event) }}" class="btn btn-primary">
                                                <i class="fas fa-qrcode me-2"></i>
                                                Start Scanning
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                        <h5>No Active Events</h5>
                                        <p class="mb-0">There are no active events to scan at the moment.</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Scans -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Scans</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="recentScansTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                    <th>Scanned By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_scans as $scan)
                                    <tr>
                                        <td>{{ $scan->created_at->format('h:i A') }}</td>
                                        <td>{{ $scan->attendee->event->name }}</td>
                                        <td>{{ $scan->attendee->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $scan->scanned_by }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-history fa-2x mb-3"></i>
                                                <p class="mb-0">No scans found</p>
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
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#recentScansTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            language: {
                search: "",
                searchPlaceholder: "Search scans..."
            }
        });
    });
</script>
@endpush
@endsection 