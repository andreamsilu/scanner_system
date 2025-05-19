@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="display-6 fw-bold text-dark mb-1">QR Scanner</h2>
                    <p class="text-muted mb-0">Currently scanning for: <strong>{{ $event->name }}</strong></p>
                </div>
                <a href="{{ route('scanner.dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Scanner Section -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="qr-reader" class="w-100 max-w-md mx-auto rounded-lg overflow-hidden"></div>
                    <div id="qr-reader-results" class="mt-4"></div>
                </div>
            </div>
        </div>

        <!-- Event Info Section -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Event Information</h5>
                    <div class="mb-3">
                        <label class="text-muted d-block">Event Name</label>
                        <strong>{{ $event->name }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Date</label>
                        <strong>{{ $event->start_date->format('M d, Y') }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Venue</label>
                        <strong>{{ $event->venue }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block">Location</label>
                        <strong>{{ $event->location }}</strong>
                    </div>
                </div>
            </div>

            <!-- Recent Scans for this Event -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Scans</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->scanLogs()->with('attendee')->latest()->take(5)->get() as $scan)
                                    <tr>
                                        <td>{{ $scan->created_at->format('h:i A') }}</td>
                                        <td>{{ $scan->attendee->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-history fa-2x mb-3"></i>
                                                <p class="mb-0">No scans yet</p>
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
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrcodeScanner = null;
    let isScannerActive = true;

    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanner
        if (html5QrcodeScanner) {
            html5QrcodeScanner.pause();
        }

        // Show loading state
        const resultsDiv = document.getElementById('qr-reader-results');
        resultsDiv.innerHTML = `
            <div class="alert alert-info d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Validating QR code...</span>
            </div>
        `;

        // Send to backend
        fetch('{{ route('scanner.validate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ token: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Check-in Successful!</h6>
                            <p class="mb-0">${data.attendee.name} has been checked in.</p>
                        </div>
                    </div>
                `;
                // Refresh the recent scans table
                location.reload();
            } else {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Check-in Failed</h6>
                            <p class="mb-0">${data.message}</p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Error</h6>
                        <p class="mb-0">An error occurred while processing the QR code.</p>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            // Resume scanner after 3 seconds
            setTimeout(() => {
                if (html5QrcodeScanner && isScannerActive) {
                    html5QrcodeScanner.resume();
                }
            }, 3000);
        });
    }

    function onScanFailure(error) {
        // Handle scan failure, usually ignore
        console.warn(`QR code scan error: ${error}`);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const qrReader = document.getElementById('qr-reader');

        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader",
            { 
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            },
            false
        );

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });
</script>
@endpush
@endsection 