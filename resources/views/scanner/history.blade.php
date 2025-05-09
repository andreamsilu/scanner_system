@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="display-6 fw-bold text-dark">Scan History</h2>
                <a href="{{ route('scanner.export') }}" class="btn btn-primary">
                    <i class="fas fa-download me-2"></i>
                    Export
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('scanner.history') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event</label>
                    <select class="form-select" name="event_id">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>
                        Filter
                    </button>
                    <a href="{{ route('scanner.history') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scan History Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Attendee</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Scanned By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scans as $scan)
                            <tr>
                                <td>{{ $scan->created_at->format('M d, Y h:i A') }}</td>
                                <td>{{ $scan->attendee->name }}</td>
                                <td>{{ $scan->event->name }}</td>
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
                                        <i class="fas fa-search fa-2x mb-3"></i>
                                        <p>No scan records found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($scans->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $scans->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection 