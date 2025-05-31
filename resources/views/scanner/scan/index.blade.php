<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <a href="{{ route('scanner.dashboard') }}" class="btn btn-sm btn-link text-muted p-0 me-3">
                    <i class="bi bi-arrow-left fs-4"></i>
                </a>
                <h2 class="h4 mb-0">QR Code Scanner</h2>
            </div>
            @if(session('selected_event_id'))
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small">
                        {{ $event->name }}
                    </span>
                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-times me-1"></i>Deselect
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <!-- Page Content -->
    <div class="row g-4">
        <!-- Scanner Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">QR Code Scanner</h5>
                        @if($event)
                            <button type="button" class="btn btn-sm btn-primary" onclick="startScanner()">
                                <i class="fas fa-play me-1"></i>Start Scanning
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($event)
                        <div class="scanner-container position-relative">
                            <!-- Scanner Status -->
                            <div class="scanner-status mb-3">
                                <span class="badge bg-secondary" id="scannerStatus">Scanner Inactive</span>
                            </div>

                            <!-- Scanner View -->
                            <div class="text-center mb-4">
                                <div id="reader" class="scanner-view mx-auto" style="width: 100%; max-width: 400px; min-height: 300px; background: #000; position: relative;">
                                    <!-- Camera feed will be inserted here -->
                                </div>
                            </div>

                            <!-- Scanner Messages -->
                            <div id="scannerMessage" class="alert d-none mb-4"></div>

                            <!-- Manual Entry Form -->
                            <div class="mt-4">
                                <form id="manualEntryForm" class="mb-4">
                                    @csrf
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="manualToken" 
                                               placeholder="Enter ticket token manually"
                                               pattern="[A-Za-z0-9]+"
                                               title="Please enter a valid token">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-search me-1"></i>Validate
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                            <h5>No Event Selected</h5>
                            <p class="text-muted">Please select an event to start scanning QR codes.</p>
                            <a href="{{ route('scanner.events.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar me-1"></i> Select Event
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Scans -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Scans</h5>
                    <a href="{{ route('scanner.history') }}" class="btn btn-sm btn-link p-0">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($recent_scans as $scan)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $scan['attendee'] }}</h6>
                                        <small class="text-muted">{{ $scan['time'] }}</small>
                                        <small class="d-block text-muted">Scanner: {{ $scan['scanner'] }}</small>
                                    </div>
                                    <span class="badge rounded-pill 
                                        @if($scan['status_class'] === 'success') bg-success
                                        @elseif($scan['status_class'] === 'warning') bg-warning
                                        @else bg-danger @endif">
                                        {{ ucfirst($scan['status']) }}
                                            </span>
                                </div>
                            </div>
                                @empty
                            <div class="text-center py-4">
                                <i class="bi bi-upc-scan display-4 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent scans</p>
                            </div>
                                @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('meta')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    @endpush

    @push('styles')
    <style>
        .scanner-container {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .scanner-view {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        #reader {
            width: 100% !important;
        }
        #reader video {
            width: 100% !important;
            height: auto !important;
            border-radius: 0.5rem;
            object-fit: cover;
            max-height: 300px;
        }
        .scanner-controls {
            background: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .scanner-status .badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        .scanner-buttons .btn {
            min-width: 100px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .input-group .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .input-group .form-control {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
        }
        .list-group-item {
            padding: 1rem;
        }
        .list-group-item:first-child {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .list-group-item:last-child {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        /* iOS-specific styles */
        @supports (-webkit-touch-callout: none) {
            #reader {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
            }
            
            #reader video {
                -webkit-transform: translateZ(0);
                transform: translateZ(0);
                object-fit: cover;
            }
        }

        /* Prevent iOS zoom on input focus */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"] {
            font-size: 16px !important;
        }

        /* Prevent pull-to-refresh on iOS */
        body {
            overscroll-behavior-y: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .scanner-container {
                padding: 1rem;
            }
            .card-header {
                padding: 0.75rem 1rem;
            }
            .card-body {
                padding: 1rem;
            }
            .scanner-view {
                min-height: 250px;
            }
            #reader video {
                max-height: 250px;
            }
            .list-group-item {
                padding: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .scanner-view {
                min-height: 200px;
            }
            #reader video {
                max-height: 200px;
            }
            .card-header h5 {
                font-size: 1rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
    let html5QrcodeScanner = null;
    let isScanning = false;

    async function startScanner() {
        try {
            // Request camera permission explicitly
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "environment",
                    width: { min: 320, ideal: 640, max: 1280 },
                    height: { min: 240, ideal: 480, max: 720 }
                } 
            });
            
            // Stop the stream as we'll use it through the scanner
            stream.getTracks().forEach(track => track.stop());

            const response = await fetch('{{ route("scanner.scan.start") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                // Update UI
                document.getElementById('scannerStatus').className = 'badge bg-success';
                document.getElementById('scannerStatus').textContent = 'Scanner Active';
                
                // Initialize scanner with built-in buttons
                if (!html5QrcodeScanner) {
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader",
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            aspectRatio: 1.0,
                            videoConstraints: {
                                facingMode: "environment",
                                width: { min: 320, ideal: 640, max: 1280 },
                                height: { min: 240, ideal: 480, max: 720 }
                            },
                            formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ],
                            showTorchButtonIfSupported: true,
                            showZoomSliderIfSupported: true,
                            defaultZoomValueIfSupported: 1.5,
                            rememberLastUsedCamera: true,
                            showCameraSelectionIfSupported: true
                        }
                    );

                    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                }
                
                isScanning = true;
                showMessage('Scanner started successfully.', 'success');
            } else {
                showMessage(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error starting scanner:', error);
            let errorMessage = 'Failed to start scanner. ';
            
            if (error.name === 'NotAllowedError') {
                errorMessage += 'Camera access was denied. Please allow camera access in your browser settings.';
            } else if (error.name === 'NotFoundError') {
                errorMessage += 'No camera found. Please ensure your device has a camera.';
            } else if (error.name === 'NotReadableError') {
                errorMessage += 'Camera is already in use by another application.';
            } else if (error.name === 'SecurityError') {
                errorMessage += 'Camera access is not allowed in this context. Please use HTTPS.';
            }
            
            showMessage(errorMessage, 'danger');
        }
    }

    function showMessage(message, type = 'info') {
        const messageDiv = document.getElementById('scannerMessage');
        messageDiv.className = `alert alert-${type} mb-3`;
        messageDiv.textContent = message;
        messageDiv.classList.remove('d-none');
                    
        // Hide message after 5 seconds
                    setTimeout(() => {
            messageDiv.classList.add('d-none');
        }, 5000);
                }

    async function onScanSuccess(decodedText, decodedResult) {
        if (!isScanning) return;

        try {
            const response = await fetch('{{ route("scanner.scan.validate") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: decodedText })
            });

            const data = await response.json();
            
            // Play sound based on result
            const audio = new Audio(data.success ? 
                '{{ asset("sounds/success.mp3") }}' : 
                '{{ asset("sounds/error.mp3") }}'
            );
            audio.play();

            showMessage(data.message, data.success ? 'success' : 'danger');
                
            // If successful, refresh the page after a short delay to update recent scans
            if (data.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            console.error('Error processing scan:', error);
            showMessage('An error occurred while processing the scan. Please try again.', 'danger');
        }
        }

        function onScanFailure(error) {
        // Handle scan failure silently
        console.warn(`QR code scan failed: ${error}`);
        }

    // Check scanner status on page load
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('{{ route("scanner.scan.status") }}');
            const data = await response.json();
            
            if (data.success && data.status === 'active') {
                startScanner();
            }
        } catch (error) {
            console.error('Error checking scanner status:', error);
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', async function() {
        if (html5QrcodeScanner) {
            await html5QrcodeScanner.clear();
        }
        if (isScanning) {
            await stopScanner();
        }
        });
    </script>
    @endpush
</x-app-layout> 