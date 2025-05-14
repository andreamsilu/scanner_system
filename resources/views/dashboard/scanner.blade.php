@extends('layouts.app')

@section('title', 'Scanner Dashboard - Event System')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Scanner Dashboard</h2>
    <div class="text-muted">
        Welcome, {{ Auth::user()->name }}
    </div>
</div>

<!-- Scan Area -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="scan-area" id="scanArea">
            <i class="bi bi-upc-scan scan-icon"></i>
            <h4>Click to Scan or Drag QR Code Here</h4>
            <p class="text-muted">Scan event tickets to check-in attendees</p>
        </div>
    </div>
</div>

<!-- Today's Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Scans Today</h6>
                        <h3 class="mb-0">45</h3>
                    </div>
                    <i class="bi bi-upc-scan text-primary" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Valid Scans</h6>
                        <h3 class="mb-0">42</h3>
                    </div>
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Invalid Scans</h6>
                        <h3 class="mb-0">3</h3>
                    </div>
                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Scans -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Recent Scans</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Attendee</th>
                        <th>Event</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2 mins ago</td>
                        <td>Jane Smith</td>
                        <td>Tech Conference 2024</td>
                        <td><span class="badge bg-success">Valid</span></td>
                    </tr>
                    <tr>
                        <td>5 mins ago</td>
                        <td>John Doe</td>
                        <td>Tech Conference 2024</td>
                        <td><span class="badge bg-success">Valid</span></td>
                    </tr>
                    <tr>
                        <td>10 mins ago</td>
                        <td>Invalid Ticket</td>
                        <td>Tech Conference 2024</td>
                        <td><span class="badge bg-danger">Invalid</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .scan-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .scan-area:hover {
        border-color: #0d6efd;
        background: rgba(13, 110, 253, 0.05);
    }
    .scan-icon {
        font-size: 3rem;
        color: #0d6efd;
        margin-bottom: 1rem;
    }
</style>
@endpush 