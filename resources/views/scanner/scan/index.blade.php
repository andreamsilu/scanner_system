<x-app-layout>
    <x-page-header 
        title="QR Code Scanner"
        :breadcrumbs="[
            'Dashboard' => route('scanner.dashboard'),
            'QR Code Scanner' => null
        ]"
    >
        @if(session('selected_event_id'))
            <x-slot name="actions">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">
                        Selected Event: {{ $event->name }}
                    </span>
                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link text-danger p-0">
                            <i class="fas fa-times me-1"></i> Deselect Event
                        </button>
                    </form>
        </div>
    </x-slot>
        @endif
    </x-page-header>

    <!-- Page Content -->
    <div class="row g-4">
        <!-- Scanner Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">QR Code Scanner</h5>
                </div>
                <div class="card-body">
                    @if($event)
                        <div class="scanner-container position-relative">
                            <!-- Scanner Status and Controls -->
                            <div class="scanner-controls mb-3 d-flex justify-content-between align-items-center">
                                <div class="scanner-status">
                                    <span class="badge bg-secondary" id="scannerStatus">Scanner Inactive</span>
                                </div>
                                <div class="scanner-buttons">
                                    <button type="button" class="btn btn-success" id="startScanner" onclick="startScanner()">
                                        <i class="fas fa-play me-1"></i> Start Scanner
                                    </button>
                                    <button type="button" class="btn btn-danger d-none" id="stopScanner" onclick="stopScanner()">
                                        <i class="fas fa-stop me-1"></i> Stop Scanner
                                    </button>
                                </div>
                            </div>

                            <!-- Scanner View -->
                            <div id="reader" class="scanner-view mb-3" style="min-height: 400px; background: #000; position: relative;">
                                <!-- Camera feed will be inserted here -->
                            </div>

                            <!-- Scanner Messages -->
                            <div id="scannerMessage" class="alert d-none mb-3"></div>

                            <!-- Selected Event Info -->
                            <div class="selected-event-info alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Selected Event:</strong> {{ $event->name }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times me-1"></i> Deselect Event
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Scans -->
                        <div class="mt-4">
                            <h6 class="mb-3">Recent Scans</h6>
                            <div class="list-group">
                                @forelse($recent_scans as $scan)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $scan['attendee'] }}</h6>
                                                <small class="text-muted">
                                                    Scanned by {{ $scan['scanner'] }} • {{ $scan['time'] }}
                                                </small>
                                            </div>
                                            <span class="badge bg-{{ $scan['status_class'] }}">
                                                {{ ucfirst($scan['status']) }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-center text-muted">
                                        No scans yet
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                            <h5>No Event Selected</h5>
                            <p class="text-muted">Please select an event to start scanning QR codes.</p>
                            <a href="{{ route('scanner.events.index') }}" class="btn btn-primary">
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
                    <a href="{{ route('scanner.history') }}" class="btn btn-link btn-sm p-0">View All</a>
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
            padding: 1rem;
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
            min-width: 120px;
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

        /* Improve touch targets for iOS */
        .btn {
            min-height: 44px;
            padding: 12px 20px;
        }

        /* Prevent pull-to-refresh on iOS */
        body {
            overscroll-behavior-y: none;
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
                    width: { min: 640, ideal: 1280, max: 1920 },
                    height: { min: 480, ideal: 720, max: 1080 }
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
                document.getElementById('startScanner').classList.add('d-none');
                document.getElementById('stopScanner').classList.remove('d-none');
                
                // Initialize scanner with iOS-specific settings
                if (!html5QrcodeScanner) {
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader",
                        {
                            fps: 10,
                            qrbox: { width: 300, height: 300 },
                            aspectRatio: 1.0,
                            videoConstraints: {
                                facingMode: "environment",
                                width: { min: 640, ideal: 1280, max: 1920 },
                                height: { min: 480, ideal: 720, max: 1080 }
                            },
                            formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ],
                            showTorchButtonIfSupported: true,
                            showZoomSliderIfSupported: true,
                            defaultZoomValueIfSupported: 2
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

    async function stopScanner() {
        try {
            const response = await fetch('{{ route("scanner.scan.stop") }}', {
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
                document.getElementById('scannerStatus').className = 'badge bg-secondary';
                document.getElementById('scannerStatus').textContent = 'Scanner Inactive';
                document.getElementById('startScanner').classList.remove('d-none');
                document.getElementById('stopScanner').classList.add('d-none');
                
                // Stop scanner
                if (html5QrcodeScanner) {
                    await html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }
                
                isScanning = false;
                showMessage('Scanner stopped successfully.', 'info');
            } else {
                showMessage(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error stopping scanner:', error);
            showMessage('Failed to stop scanner. Please try again.', 'danger');
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