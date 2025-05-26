<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">Scan Tickets - {{ $event->name }}</h2>
                <p class="text-muted mb-0">Scan QR codes to check in attendees</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('scanner.events.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Events
                </a>
                <a href="{{ route('scanner.deselect-event', ['event' => $event->id]) }}" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-2"></i>Deselect Event
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Scanner Section -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div id="reader" class="mx-auto" style="width: 100%; max-width: 600px;"></div>
                        </div>

                        <!-- Manual Entry Form -->
                        <div class="mt-4">
                            <form id="manualEntryForm" class="mb-4">
                                @csrf
                                <div class="input-group input-group-lg">
                                    <input type="text" 
                                           class="form-control" 
                                           id="manualToken" 
                                           placeholder="Enter ticket token manually"
                                           pattern="[A-Za-z0-9]+"
                                           title="Please enter a valid token">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-2"></i>Validate
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Scan Result -->
                        <div id="scanResult" class="alert d-none mt-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-1"></h5>
                                    <p class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Scans Section -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Scans</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($recent_scans as $scan)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $scan['attendee'] }}</h6>
                                            <small class="text-muted">
                                                {{ $scan['time'] }} by {{ $scan['scanner'] }}
                                            </small>
                                        </div>
                                        <span class="badge bg-{{ $scan['status_class'] }}">
                                            {{ ucfirst($scan['status']) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-4 mb-2"></i>
                                    <p class="mb-0">No scans yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // Initialize QR Scanner
        const html5QrCode = new Html5Qrcode("reader");
        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            // Stop scanning after successful scan
            html5QrCode.stop().then(() => {
                validateToken(decodedText);
            });
        };

        const config = { 
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        // Start scanning
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            qrCodeSuccessCallback
        );

        // Handle manual entry form
        document.getElementById('manualEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const token = document.getElementById('manualToken').value;
            if (token) {
                validateToken(token);
                this.reset();
            }
        });

        // Function to validate token
        function validateToken(token) {
            fetch('{{ route("scanner.scan.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('scanResult');
                const icon = resultDiv.querySelector('.bi');
                const heading = resultDiv.querySelector('.alert-heading');
                const message = resultDiv.querySelector('p');

                resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
                
                if (data.success) {
                    resultDiv.classList.add('alert-success');
                    icon.classList.add('bi-check-circle-fill');
                    heading.textContent = 'Check-in Successful!';
                    message.textContent = `${data.attendee.name} has been checked in.`;
                } else {
                    if (data.code === 'TICKET_USED') {
                        resultDiv.classList.add('alert-warning');
                        icon.classList.add('bi-exclamation-triangle-fill');
                    } else {
                        resultDiv.classList.add('alert-danger');
                        icon.classList.add('bi-x-circle-fill');
                    }
                    heading.textContent = 'Check-in Failed';
                    message.textContent = data.message;
                }

                // Show result for 3 seconds then hide
                setTimeout(() => {
                    resultDiv.classList.add('d-none');
                }, 3000);

                // Resume scanning
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    qrCodeSuccessCallback
                );
            })
            .catch(error => {
                console.error('Error:', error);
                const resultDiv = document.getElementById('scanResult');
                resultDiv.classList.remove('d-none', 'alert-success', 'alert-warning');
                resultDiv.classList.add('alert-danger');
                resultDiv.querySelector('.bi').classList.add('bi-x-circle-fill');
                resultDiv.querySelector('.alert-heading').textContent = 'Error';
                resultDiv.querySelector('p').textContent = 'An error occurred while processing the scan.';

                // Resume scanning
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    qrCodeSuccessCallback
                );
            });
        }
    </script>
    @endpush
</x-app-layout> 