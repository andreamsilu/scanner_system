@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="display-6 fw-bold text-dark">Revenue Report</h2>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('reports.revenue') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Event Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter me-2"></i>Filter
                    </button>
                    <a href="{{ route('reports.revenue') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total Tickets</th>
                            <th>Total Revenue</th>
                            <th>Average Ticket Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>{{ $event->name }}</td>
                                <td>{{ $event->date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'completed' ? 'info' : 'danger') }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </td>
                                <td>{{ $event->tickets_count }}</td>
                                <td>${{ number_format($event->tickets_sum_price, 2) }}</td>
                                <td>
                                    @if($event->tickets_count > 0)
                                        ${{ number_format($event->tickets_sum_price / $event->tickets_count, 2) }}
                                    @else
                                        $0.00
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-calendar-x fa-2x mb-3"></i>
                                        <p class="mb-0">No events found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($events->hasPages())
            <div class="card-footer bg-white">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>
@endsection 