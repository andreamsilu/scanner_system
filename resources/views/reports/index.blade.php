@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="display-6 fw-bold text-dark">Reports Dashboard</h2>
                <div>
                    <a href="{{ route('reports.attendance') }}" class="btn btn-primary me-2">
                        <i class="bi bi-people me-2"></i>Attendance Report
                    </a>
                    <a href="{{ route('reports.revenue') }}" class="btn btn-success">
                        <i class="bi bi-currency-dollar me-2"></i>Revenue Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-calendar-event text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Events</h6>
                            <h4 class="mb-0">{{ $stats['total_events'] }}</h4>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <small class="text-success">
                            {{ $stats['active_events'] }} Active
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Attendees</h6>
                            <h4 class="mb-0">{{ $stats['total_attendees'] }}</h4>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <small class="text-muted">
                            Across all events
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-upc-scan text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Scans</h6>
                            <h4 class="mb-0">{{ $stats['total_scans'] }}</h4>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <small class="text-success">
                            {{ $stats['successful_scans'] }} Successful
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-exclamation-triangle text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Failed Scans</h6>
                            <h4 class="mb-0">{{ $stats['failed_scans'] }}</h4>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <small class="text-muted">
                            {{ $stats['total_scans'] > 0 
                                ? number_format(($stats['failed_scans'] / $stats['total_scans']) * 100, 1) 
                                : '0.0' 
                            }}% Rate
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Daily Scan Trends -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Daily Scan Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyScansChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Events</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recent_events as $event)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $event->name }}</h6>
                                        <small class="text-muted">
                                            {{ $event->attendees_count }} Attendees
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $event->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted fa-2x mb-3"></i>
                                <p class="text-muted mb-0">No events found</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Scans Chart
    const ctx = document.getElementById('dailyScansChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($daily_scans->pluck('date')),
            datasets: [{
                label: 'Total Scans',
                data: @json($daily_scans->pluck('total')),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Successful Scans',
                data: @json($daily_scans->pluck('successful')),
                borderColor: 'rgb(34, 197, 94)',
                tension: 0.1
            }, {
                label: 'Failed Scans',
                data: @json($daily_scans->pluck('failed')),
                borderColor: 'rgb(239, 68, 68)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
});
</script>
@endpush
@endsection 