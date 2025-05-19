<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h3 mb-0">Scan QR Code</h2>
            <div>
                <a href="{{ route('scanner.events.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Events
                </a>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Scan QR Code</h5>
                    <div id="reader" class="w-100"></div>
                    <div id="result" class="mt-3"></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Recent Scans</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scans as $scan)
                                    <tr>
                                        <td>{{ $scan->created_at->format('h:i A') }}</td>
                                        <td>{{ $scan->ticket->attendee->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No scans yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $scans->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            html5QrcodeScanner.pause();
            
            // Send the token to the server
            fetch('{{ route("scanner.scan.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    token: decodedText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                    
                    // Reload the page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    document.getElementById('result').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                    
                    // Resume scanning after 2 seconds
                    setTimeout(() => {
                        html5QrcodeScanner.resume();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        An error occurred while processing the scan.
                    </div>
                `;
                
                // Resume scanning after 2 seconds
                setTimeout(() => {
                    html5QrcodeScanner.resume();
                }, 2000);
            });
        }

        function onScanFailure(error) {
            // Handle scan failure, usually ignore
            console.warn(`QR code scan error: ${error}`);
        }

        let html5QrcodeScanner = null;

        document.addEventListener('DOMContentLoaded', function() {
            const reader = document.getElementById('reader');
            
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
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
</x-app-layout> 