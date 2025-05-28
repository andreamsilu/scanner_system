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
                        <button type="submit" class="btn btn-link text-danger p-0">Deselect Event</button>
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
                <div class="card-body">
                    <div class="position-relative">
                        <video id="scanner" class="w-100 rounded"></video>
                        <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none justify-content-center align-items-center">
                            <div class="spinner-border text-light" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div id="scanResult" class="mt-3"></div>
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

    @if(!session('selected_event_id'))
        <div class="card mt-4">
            <div class="card-body text-center">
                <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                <h5 class="card-title">No Event Selected</h5>
                <p class="card-text text-muted">Please select an event to start scanning tickets</p>
                <a href="{{ route('scanner.events.index') }}" class="btn btn-primary">
                    <i class="bi bi-calendar-event me-2"></i>Select Event
                </a>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrCode = null;

        function onScanSuccess(decodedText, decodedResult) {
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.remove('d-none');
            
            // Stop scanning temporarily
            if (html5QrCode) {
                html5QrCode.pause();
            }

            // Send the scanned token to the server
            fetch('{{ route("scanner.scan.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ token: decodedText })
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('scanResult');
                resultDiv.classList.remove('d-none');
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success d-flex align-items-center mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Check-in Successful</h6>
                                <p class="mb-0">Attendee: ${data.attendee.name}</p>
                                <p class="mb-0">Email: ${data.attendee.email}</p>
                                <p class="mb-0">Checked in at: ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>
                            </div>
                        </div>
                    `;
                    
                    // Play success sound
                    const audio = new Audio('/sounds/success.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger d-flex align-items-center mb-0">
                            <i class="bi bi-x-circle me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Scan Failed</h6>
                                <p class="mb-0">${data.message}</p>
                            </div>
                        </div>
                    `;
                    
                    // Play error sound
                    const audio = new Audio('/sounds/error.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                }

                // Resume scanning after a short delay
                setTimeout(() => {
                    document.getElementById('loadingOverlay').classList.add('d-none');
                    if (html5QrCode) {
                        html5QrCode.resume();
                    }
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                const resultDiv = document.getElementById('scanResult');
                resultDiv.classList.remove('d-none');
                resultDiv.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center mb-0">
                        <i class="bi bi-x-circle me-2"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error</h6>
                            <p class="mb-0">An error occurred while processing the scan. Please try again.</p>
                        </div>
                    </div>
                `;
                
                // Resume scanning after error
                document.getElementById('loadingOverlay').classList.add('d-none');
                if (html5QrCode) {
                    html5QrCode.resume();
                }
            });
        }

        function onScanFailure(error) {
            // Handle scan failure, usually ignore
            console.warn(`QR code scan error: ${error}`);
        }

        // Initialize the scanner when the page loads
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const videoElement = document.getElementById('scanner');
                html5QrCode = new Html5Qrcode("scanner");
                
                // Get available cameras
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    // Start with the first camera (usually the back camera on mobile)
                    const cameraId = devices[0].id;
                    await html5QrCode.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            aspectRatio: 1.0
                        },
                        onScanSuccess,
                        onScanFailure
                    );
                } else {
                    throw new Error('No cameras found');
                }
            } catch (error) {
                console.error('Error starting scanner:', error);
                const resultDiv = document.getElementById('scanResult');
                resultDiv.classList.remove('d-none');
                resultDiv.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center mb-0">
                        <i class="bi bi-x-circle me-2"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Camera Error</h6>
                            <p class="mb-0">Unable to access the camera. Please ensure you have granted camera permissions and try again.</p>
                        </div>
                    </div>
                `;
            }
        });

        // Clean up when the page is unloaded
        window.addEventListener('beforeunload', async function() {
            if (html5QrCode) {
                try {
                    await html5QrCode.stop();
                } catch (error) {
                    console.error('Error stopping scanner:', error);
                }
            }
        });
    </script>
    @endpush
</x-app-layout> 