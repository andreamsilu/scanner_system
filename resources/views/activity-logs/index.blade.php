@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Activity Logs</h2>
        <a href="{{ route('activity-logs.export') }}" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Activities</h6>
                    <h2 class="card-title mb-0">{{ $stats['total_activities'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Today's Activities</h6>
                    <h2 class="card-title mb-0">{{ $stats['today_activities'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Unique Users</h6>
                    <h2 class="card-title mb-0">{{ $stats['unique_users'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Avg. Activities/Day</h6>
                    <h2 class="card-title mb-0">{{ $stats['avg_activities_per_day'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('activity-logs') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by user, description, or IP..."
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="login" {{ request('type') === 'login' ? 'selected' : '' }}>Login</option>
                        <option value="logout" {{ request('type') === 'logout' ? 'selected' : '' }}>Logout</option>
                        <option value="create" {{ request('type') === 'create' ? 'selected' : '' }}>Create</option>
                        <option value="update" {{ request('type') === 'update' ? 'selected' : '' }}>Update</option>
                        <option value="delete" {{ request('type') === 'delete' ? 'selected' : '' }}>Delete</option>
                    </select>
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

    <!-- Activity Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->type === 'login' ? 'success' : 
                                        ($log->type === 'logout' ? 'warning' : 
                                        ($log->type === 'create' ? 'primary' : 
                                        ($log->type === 'update' ? 'info' : 'danger'))) }}">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->ip_address }}</td>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No activity logs found.</td>
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
@endsection 