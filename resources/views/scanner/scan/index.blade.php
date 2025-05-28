<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('QR Code Scanner') }}
            </h2>
            @if(session('selected_event_id'))
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        Selected Event: {{ $event->name }}
                    </span>
                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            Deselect Event
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- QR Code Scanner -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Scan QR Code</h3>
                            <div class="relative">
                                <video id="scanner" class="w-full h-64 bg-gray-100 rounded-lg"></video>
                                <div id="scanOverlay" class="absolute inset-0 flex items-center justify-center hidden">
                                    <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
                                </div>
                            </div>
                            <div id="scanResult" class="mt-4 hidden">
                                <!-- Scan result will be shown here -->
                            </div>
                        </div>

                        <!-- Recent Scans -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Scans</h3>
                            <div class="space-y-4">
                                @forelse($recent_scans as $scan)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $scan['attendee'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $scan['time'] }}</p>
                                            <p class="text-xs text-gray-500">Scanner: {{ $scan['scanner'] }}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            @if($scan['status_class'] === 'success') bg-green-100 text-green-800
                                            @elseif($scan['status_class'] === 'warning') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($scan['status']) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No recent scans</p>
                                @endforelse
                            </div>
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

        function onScanSuccess(decodedText, decodedResult) {
            // Show loading overlay
            document.getElementById('scanOverlay').classList.remove('hidden');
            
            // Stop scanning temporarily
            if (html5QrcodeScanner) {
                html5QrcodeScanner.pause();
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
                resultDiv.classList.remove('hidden');
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Check-in Successful</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>Attendee: ${data.attendee.name}</p>
                                        <p>Email: ${data.attendee.email}</p>
                                        <p>Checked in at: ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Play success sound
                    const audio = new Audio('/sounds/success.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                } else {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Scan Failed</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>${data.message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Play error sound
                    const audio = new Audio('/sounds/error.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                }

                // Resume scanning after a short delay
                setTimeout(() => {
                    document.getElementById('scanOverlay').classList.add('hidden');
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.resume();
                    }
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                const resultDiv = document.getElementById('scanResult');
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = `
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>An error occurred while processing the scan. Please try again.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Resume scanning after error
                document.getElementById('scanOverlay').classList.add('hidden');
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.resume();
                }
            });
        }

        function onScanFailure(error) {
            // Handle scan failure, usually ignore
            console.warn(`QR code scan error: ${error}`);
        }

        // Initialize the scanner when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "scanner",
                { 
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                }
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });

        // Clean up when the page is unloaded
        window.addEventListener('beforeunload', function() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
        });
    </script>
    @endpush
</x-app-layout> 