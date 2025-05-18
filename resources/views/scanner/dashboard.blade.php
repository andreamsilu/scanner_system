@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="{{ asset('images/logo.png') }}" 
                             alt="Event System Logo" 
                             class="system-logo" 
                             style="height: 60px; width: auto;">
                    </div>
                    <div>
                        <h2 class="display-6 fw-bold text-dark mb-1">Scanner Dashboard</h2>
                        <p class="text-muted mb-0">Select an event to start scanning</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-primary-100 text-primary-600 me-3">
                            <i class="fas fa-qrcode fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Today's Scans</h6>
                            <h2 class="card-title mb-0">{{ $today_scans }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-success-100 text-success-600 me-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Total Scans</h6>
                            <h2 class="card-title mb-0">{{ $total_scans }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-info-100 text-info-600 me-3">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Checked In Today</h6>
                            <h2 class="card-title mb-0">{{ $checked_in_today }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-purple-100 text-purple-600 me-3">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Assigned Events</h6>
                            <h2 class="card-title mb-0">{{ $assigned_events->count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Events -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        @if(auth()->user()->hasRole('admin'))
                            Available Events
                        @else
                            Your Assigned Events
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($assigned_events as $event)
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
                                <div class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                        <h5>
                                            @if(auth()->user()->hasRole('admin'))
                                                No Active Events
                                            @else
                                                No Assigned Events
                                            @endif
                                        </h5>
                                        <p class="mb-0">
                                            @if(auth()->user()->hasRole('admin'))
                                                There are no active events available at the moment.
                                            @else
                                                You have not been assigned to any events yet. Please contact your administrator.
                                            @endif
                                        </p>
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Scans</h5>
                    <a href="{{ route('scanner.history') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-history me-2"></i>
                        View History
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_scans as $scan)
                                    <tr>
                                        <td>{{ $scan->created_at->format('h:i A') }}</td>
                                        <td>{{ $scan->event->name }}</td>
                                        <td>{{ $scan->attendee->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-qrcode fa-2x mb-3"></i>
                                                <p class="mb-0">No recent scans found.</p>
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

@push('styles')
<style>
    .system-logo {
        object-fit: contain;
        transition: transform 0.2s ease-in-out;
    }
    .system-logo:hover {
        transform: scale(1.05);
    }
    @media (max-width: 768px) {
        .system-logo {
            height: 40px !important;
        }
    }
</style>
@endpush

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