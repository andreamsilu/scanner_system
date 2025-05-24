<x-app-layout>
    <x-page-header 
        title="Reports Dashboard"
        :breadcrumbs="['Reports' => null]"
    >
        <x-slot name="actions">
            <a href="{{ route('reports.attendance') }}" class="btn btn-primary">
                <i class="bi bi-people me-2"></i>Attendance Report
            </a>
            <a href="{{ route('reports.revenue') }}" class="btn btn-success">
                <i class="bi bi-currency-dollar me-2"></i>Revenue Report
            </a>
        </x-slot>
    </x-page-header>

    <!-- Statistics Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-md-4 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-2 p-sm-3 rounded">
                                <i class="bi bi-calendar-event text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Total Events</h6>
                            <h4 class="mb-0 fs-5 fs-sm-4">{{ $stats['total_events'] }}</h4>
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

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-2 p-sm-3 rounded">
                                <i class="bi bi-people text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Total Attendees</h6>
                            <h4 class="mb-0 fs-5 fs-sm-4">{{ $stats['total_attendees'] }}</h4>
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

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-2 p-sm-3 rounded">
                                <i class="bi bi-upc-scan text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Total Scans</h6>
                            <h4 class="mb-0 fs-5 fs-sm-4">{{ $stats['total_scans'] }}</h4>
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

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-2 p-sm-3 rounded">
                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Failed Scans</h6>
                            <h4 class="mb-0 fs-5 fs-sm-4">{{ $stats['failed_scans'] }}</h4>
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

    <div class="row g-3 g-md-4">
        <!-- Daily Scan Trends -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fs-6">Daily Scan Trends</h5>
                </div>
                <div class="card-body">
                    <div style="min-height: 300px;">
                        <canvas id="dailyScansChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fs-6">Recent Events</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recent_events as $event)
                            <div class="list-group-item px-3 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="me-3">
                                        <h6 class="mb-1 text-truncate" style="max-width: 200px;">{{ $event->name }}</h6>
                                        <small class="text-muted">
                                            {{ $event->attendees_count }} Attendees
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $event->status === 'active' ? 'success' : 'secondary' }} flex-shrink-0">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted fs-4 mb-3"></i>
                                <p class="text-muted mb-0">No events found</p>
                            </div>
                        @endforelse
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
        const chart = new Chart(ctx, {
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
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            stepSize: 1,
                            precision: 0
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                }
            }
        });

        // Handle window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                chart.resize();
            }, 250);
        });
    });
    </script>
    @endpush
</x-app-layout> 