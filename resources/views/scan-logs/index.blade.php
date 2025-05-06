@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Scan Logs</h2>
        <a href="{{ route('scan-logs.export') }}" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
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
                    <h6 class="card-subtitle mb-2 text-muted">Today's Scans</h6>
                    <h2 class="card-title mb-0">{{ $stats['today_scans'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Unique Scanners</h6>
                    <h2 class="card-title mb-0">{{ $stats['unique_scanners'] }}</h2>
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

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('scan-logs') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by attendee or scanner..."
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="date_from" 
                           value="{{ request('date_from') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="date_to" 
                           value="{{ request('date_to') }}" placeholder="To Date">
                </div>
            </form>
        </div>
    </div>

    <!-- Scan Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Attendee</th>
                            <th>Scanned By</th>
                            <th>Device Info</th>
                            <th>Scanned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->attendee->name }}</td>
                                <td>{{ $log->scanned_by }}</td>
                                <td>{{ $log->device_info }}</td>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No scan logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Add any JavaScript functionality here
</script>
@endpush
@endsection 