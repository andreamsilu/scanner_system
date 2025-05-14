@extends('layouts.app')

@section('title', 'Admin Dashboard - Event System')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Admin Dashboard</h2>
    <div class="text-muted">
        Welcome, {{ Auth::user()->name }}
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Events</h6>
                        <h3 class="mb-0">12</h3>
                    </div>
                    <i class="bi bi-calendar-event stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Attendees</h6>
                        <h3 class="mb-0">1,234</h3>
                    </div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Active Scanners</h6>
                        <h3 class="mb-0">8</h3>
                    </div>
                    <i class="bi bi-person-badge stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Scans Today</h6>
                        <h3 class="mb-0">156</h3>
                    </div>
                    <i class="bi bi-upc-scan stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Recent Activity</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Event</th>
                        <th>Scanner</th>
                        <th>Attendee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2 mins ago</td>
                        <td>Tech Conference 2024</td>
                        <td>John Doe</td>
                        <td>Jane Smith</td>
                        <td><span class="badge bg-success">Scanned</span></td>
                    </tr>
                    <tr>
                        <td>5 mins ago</td>
                        <td>Tech Conference 2024</td>
                        <td>Mike Johnson</td>
                        <td>Sarah Wilson</td>
                        <td><span class="badge bg-success">Scanned</span></td>
                    </tr>
                    <tr>
                        <td>10 mins ago</td>
                        <td>Tech Conference 2024</td>
                        <td>John Doe</td>
                        <td>Invalid Ticket</td>
                        <td><span class="badge bg-danger">Failed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 