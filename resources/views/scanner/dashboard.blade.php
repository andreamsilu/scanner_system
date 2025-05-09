@extends('layouts.scanner')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-semibold mb-4">Scanner Dashboard</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <!-- Today's Scans Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                <i class="fas fa-qrcode text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-700">Today's Scans</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ $today_scans }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Scans Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500">
                                <i class="fas fa-history text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-700">Total Scans</h3>
                                <p class="text-3xl font-bold text-gray-900">{{ $total_scans }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Scanner -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">QR Scanner</h3>
                        <div id="qr-reader" class="w-full max-w-md mx-auto"></div>
                        <div id="qr-reader-results" class="mt-4 text-center"></div>
                    </div>
                </div>

                <!-- Recent Scans -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Recent Scans</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recent_scans as $scan)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $scan->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $scan->event->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $scan->attendee->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $scan->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($scan->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    function onScanSuccess(decodedText, decodedResult) {
        // Play success sound
        const audio = new Audio('/sounds/success.mp3');
        audio.play();

        // Send to server
        fetch('/scanner/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ token: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('qr-reader-results');
            resultDiv.innerHTML = `
                <div class="p-4 rounded-lg ${data.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${data.message}
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function onScanFailure(error) {
        // Handle scan failure
        console.warn(`QR code scan error: ${error}`);
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        { fps: 10, qrbox: {width: 250, height: 250} },
        false
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>
@endpush
@endsection 