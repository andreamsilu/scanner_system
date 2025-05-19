@extends('layouts.app')

@section('title', 'Scanner Dashboard - Event System')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="display-6 fw-bold text-dark">Scanner Dashboard</h2>
        <div class="text-muted">
            Welcome, {{ Auth::user()->name }}
        </div>
    </div>

    <!-- Scan Area -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <div class="scan-area" id="scanArea">
                        <i class="bi bi-upc-scan scan-icon"></i>
                        <h4>Click to Scan or Drag QR Code Here</h4>
                        <p class="text-muted">Scan event tickets to check-in attendees</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-upc-scan fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Scans Today</h6>
                            <h3 class="card-title mb-0">{{ $stats['today_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Valid Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['valid_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-circle bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-2 text-muted">Invalid Scans</h6>
                            <h3 class="card-title mb-0">{{ $stats['invalid_scans'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Scans -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Scans</h5>
            <a href="{{ route('scanner.validate') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-key me-2"></i>Manual Validation
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Attendee</th>
                            <th>Event</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_scans as $scan)
                            <tr>
                                <td>{{ $scan['time'] }}</td>
                                <td>{{ $scan['attendee'] }}</td>
                                <td>{{ $scan['event'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $scan['status_class'] }}">
                                        {{ ucfirst($scan['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">No recent scans</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scanArea = document.getElementById('scanArea');
    if (scanArea) {
        // Add click handler for file input
        scanArea.addEventListener('click', function() {
            // Implement QR code scanning logic here
            console.log('Scan area clicked');
        });

        // Add drag and drop handlers
        scanArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#0d6efd';
            this.style.background = 'rgba(13, 110, 253, 0.05)';
        });

        scanArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = 'transparent';
        });

        scanArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = 'transparent';
            // Implement QR code image processing here
            console.log('File dropped');
        });
    }
});
</script>
@endpush
@endsection 