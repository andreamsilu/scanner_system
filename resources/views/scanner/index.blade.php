@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">QR Code Scanner</h1>

            <!-- Scanner Status -->
            <div id="scanner-status" class="mb-4 p-4 rounded-lg bg-gray-100">
                <p class="text-gray-600">Ready to scan</p>
            </div>

            <!-- Scanner View -->
            <div class="relative mb-6">
                <video id="scanner" class="w-full h-64 bg-gray-900 rounded-lg"></video>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="border-2 border-white rounded-lg w-48 h-48"></div>
                </div>
                    </div>

            <!-- Scan Result -->
            <div id="scan-result" class="hidden mb-6 p-4 rounded-lg">
                <!-- Result content will be dynamically inserted here -->
                    </div>

            <!-- Manual Entry -->
            <div class="mb-6">
                <label for="manual-token" class="block text-sm font-medium text-gray-700 mb-2">Manual Entry</label>
                <div class="flex gap-2">
                    <input type="text" id="manual-token" 
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Enter 7-digit token"
                           maxlength="7"
                           pattern="[A-Za-z0-9]{7}">
                    <button onclick="manualScan()" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Check In
                    </button>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-2">Instructions</h2>
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    <li>Position the QR code within the scanner frame</li>
                    <li>Hold steady until the code is recognized</li>
                    <li>Or enter the 7-digit token manually</li>
                    <li>Wait for confirmation of successful check-in</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let html5QrCode = null;
let isScanning = false;

// Initialize scanner
function initializeScanner() {
    if (html5QrCode) {
        html5QrCode.stop();
    }

    html5QrCode = new Html5Qrcode("scanner");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanFailure
    ).catch(err => {
        console.error("Failed to start scanner:", err);
        updateStatus("Failed to start camera. Please check camera permissions.", "error");
    });

    isScanning = true;
}

// Handle successful scan
function onScanSuccess(decodedText) {
    if (!isScanning) return;
    
    // Stop scanning temporarily
    isScanning = false;
    html5QrCode.pause();

    // Process the scan
    processScan(decodedText);
}

// Handle scan failure
function onScanFailure(error) {
    // Ignore if we're not actively scanning
    if (!isScanning) return;
    console.warn(`Scan failed: ${error}`);
}

// Process the scan
function processScan(token) {
    updateStatus("Processing scan...", "processing");

    fetch('/scan', {
            method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ token })
    })
    .then(response => response.json())
    .then(data => {
        showResult(data);
        if (data.status === 'success') {
            // Play success sound
            playSound('success');
        } else {
            // Play error sound
            playSound('error');
        }
    })
    .catch(error => {
        console.error('Scan processing failed:', error);
        updateStatus("Failed to process scan. Please try again.", "error");
        playSound('error');
    })
    .finally(() => {
        // Resume scanning after a delay
        setTimeout(() => {
            if (html5QrCode) {
                html5QrCode.resume();
                isScanning = true;
            }
        }, 2000);
    });
}

// Manual scan entry
function manualScan() {
    const tokenInput = document.getElementById('manual-token');
    const token = tokenInput.value.trim();

    if (token.length !== 7) {
        updateStatus("Please enter a valid 7-digit token", "error");
        return;
    }

    processScan(token);
    tokenInput.value = '';
}

// Update scanner status
function updateStatus(message, type = 'info') {
    const statusDiv = document.getElementById('scanner-status');
    statusDiv.className = `mb-4 p-4 rounded-lg ${
        type === 'error' ? 'bg-red-100 text-red-700' :
        type === 'success' ? 'bg-green-100 text-green-700' :
        type === 'processing' ? 'bg-yellow-100 text-yellow-700' :
        'bg-gray-100 text-gray-600'
    }`;
    statusDiv.querySelector('p').textContent = message;
}

// Show scan result
function showResult(data) {
    const resultDiv = document.getElementById('scan-result');
    resultDiv.className = `mb-6 p-4 rounded-lg ${
        data.status === 'success' ? 'bg-green-100' : 'bg-red-100'
    }`;

    let content = `
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold ${data.status === 'success' ? 'text-green-800' : 'text-red-800'}">
                    ${data.status === 'success' ? 'Check-in Successful' : 'Check-in Failed'}
                </h3>
                <p class="text-sm ${data.status === 'success' ? 'text-green-600' : 'text-red-600'}">
                    ${data.message}
                </p>
            </div>
            <button onclick="this.parentElement.parentElement.parentElement.classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    `;

    if (data.attendee) {
        content += `
            <div class="mt-3 text-sm">
                <p><strong>Name:</strong> ${data.attendee.name}</p>
                <p><strong>Event:</strong> ${data.attendee.event}</p>
                <p><strong>Email:</strong> ${data.attendee.email}</p>
                ${data.attendee.checked_in_at ? 
                    `<p><strong>Checked in at:</strong> ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>` : 
                    ''}
            </div>
        `;
    }

    resultDiv.innerHTML = content;
    resultDiv.classList.remove('hidden');
}

// Play sound for feedback
function playSound(type) {
    const audio = new Audio(`/sounds/${type}.mp3`);
    audio.play().catch(err => console.warn('Failed to play sound:', err));
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeScanner();

    // Handle manual token input
    const tokenInput = document.getElementById('manual-token');
    tokenInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            manualScan();
        }
    });
});

// Cleanup when leaving the page
window.addEventListener('beforeunload', () => {
    if (html5QrCode) {
        html5QrCode.stop();
    }
    });
</script>
@endpush

@push('styles')
<style>
#scanner {
    position: relative;
    width: 100%;
    height: 256px;
    background-color: #000;
    border-radius: 0.5rem;
    overflow: hidden;
}

#scanner video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scanner-frame {
    width: 200px;
    height: 200px;
    border: 2px solid #fff;
    border-radius: 0.5rem;
}
</style>
@endpush
@endsection 