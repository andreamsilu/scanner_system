@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="display-6 fw-bold text-dark">Welcome back, {{ auth()->user()->name }}!</h2>
            <p class="text-muted">Here's what's happening with your event system today.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Total Users</h6>
                            <h2 class="card-title mb-0">{{ \App\Models\User::count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-user-friends fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Total Attendees</h6>
                            <h2 class="card-title mb-0">{{ \App\Models\Attendee::count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-qrcode fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Total Scans</h6>
                            <h2 class="card-title mb-0">{{ \App\Models\ScanLog::count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Scanned Today</h6>
                            <h2 class="card-title mb-0">{{ \App\Models\ScanLog::whereDate('created_at', today())->count() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="fas fa-users-cog fa-lg text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">User Management</h5>
                    </div>
                    <p class="card-text text-muted mb-4">Manage system users, roles, and permissions with ease.</p>
                    <a href="{{ route('users.index') }}" class="btn btn-primary">
                        Manage Users
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-id-card fa-lg text-success"></i>
                        </div>
                        <h5 class="card-title mb-0">Event Cards</h5>
                    </div>
                    <p class="card-text text-muted mb-4">Create and manage event cards for your attendees.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-success">
                        View Events
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="fas fa-history fa-lg text-info"></i>
                        </div>
                        <h5 class="card-title mb-0">Activity Logs</h5>
                    </div>
                    <p class="card-text text-muted mb-4">Monitor system activity and track important events.</p>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-info text-white">
                        View Logs
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Activity</h5>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-link text-decoration-none">View all</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Time</th>
                            <th class="border-0">User</th>
                            <th class="border-0">Action</th>
                            <th class="border-0">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\ActivityLog::latest()->take(5)->get() as $log)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="far fa-clock text-muted me-2"></i>
                                    {{ $log->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary bg-opacity-10 p-2 me-2">
                                        <span class="text-secondary fw-bold">
                                            {{ substr($log->user->name ?? 'S', 0, 1) }}
                                        </span>
                                    </div>
                                    {{ $log->user->name ?? 'System' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $log->action }}</span>
                            </td>
                            <td class="text-muted">{{ $log->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>
@endsection 