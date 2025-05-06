@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row g-4">
        <!-- Stats Cards -->
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Attendees</h6>
                    <h2 class="card-title mb-0">{{ $stats['total_attendees'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Scanned Attendees</h6>
                    <h2 class="card-title mb-0">{{ $stats['scanned_attendees'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Scans</h6>
                    <h2 class="card-title mb-0">{{ $stats['total_scans'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Users</h6>
                    <h2 class="card-title mb-0">{{ $stats['total_users'] }}</h2>
                </div>
            </div>
        </div>

        <!-- Recent Scans -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Scans</h5>
                    <a href="{{ route('scan-logs') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Attendee</th>
                                    <th>Scanned By</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentScans as $scan)
                                <tr>
                                    <td>{{ $scan->attendee->name }}</td>
                                    <td>{{ $scan->scanned_by }}</td>
                                    <td>{{ $scan->created_at->diffForHumans() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Scans Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Scans</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyScansChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dailyScansChart').getContext('2d');
    const data = @json($dailyScans);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Scans',
                data: data.map(item => item.total),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection 