@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Analytics Dashboard</h2>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
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
                    <h6 class="card-subtitle mb-2 text-muted">Scan Rate</h6>
                    <h2 class="card-title mb-0">{{ $stats['scan_rate'] }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Avg. Scans/Day</h6>
                    <h2 class="card-title mb-0">{{ $stats['avg_scans_per_day'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Daily Scan Trends -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Scan Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyScansChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Scanner Activity -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Scanners</h5>
                </div>
                <div class="card-body">
                    <canvas id="scannerActivityChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Hourly Distribution -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hourly Scan Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyScansChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Device Statistics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Device Statistics</h5>
                </div>
                <div class="card-body">
                    <canvas id="deviceStatsChart" height="300"></canvas>
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
    const dailyScansCtx = document.getElementById('dailyScansChart').getContext('2d');
    new Chart(dailyScansCtx, {
        type: 'line',
        data: {
            labels: @json($dailyScans->pluck('date')),
            datasets: [{
                label: 'Daily Scans',
                data: @json($dailyScans->pluck('total')),
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
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Scanner Activity Chart
    const scannerActivityCtx = document.getElementById('scannerActivityChart').getContext('2d');
    new Chart(scannerActivityCtx, {
        type: 'doughnut',
        data: {
            labels: @json($scannerActivity->pluck('scanned_by')),
            datasets: [{
                data: @json($scannerActivity->pluck('total_scans')),
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Hourly Scans Chart
    const hourlyScansCtx = document.getElementById('hourlyScansChart').getContext('2d');
    new Chart(hourlyScansCtx, {
        type: 'bar',
        data: {
            labels: @json($hourlyScans->pluck('hour')),
            datasets: [{
                label: 'Scans per Hour',
                data: @json($hourlyScans->pluck('total')),
                backgroundColor: 'rgb(75, 192, 192)'
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

    // Device Stats Chart
    const deviceStatsCtx = document.getElementById('deviceStatsChart').getContext('2d');
    new Chart(deviceStatsCtx, {
        type: 'pie',
        data: {
            labels: @json($deviceStats->pluck('device')),
            datasets: [{
                data: @json($deviceStats->pluck('total')),
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
@endpush
@endsection 