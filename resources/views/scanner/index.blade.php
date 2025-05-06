@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">QR Code Scanner</h4>
                </div>
                <div class="card-body">
                    <div id="scanner-container" class="mb-4">
                        <div id="reader" class="w-100"></div>
                    </div>

                    <div id="scan-result" class="d-none">
                        <div class="alert alert-success">
                            <h5 class="alert-heading">Scan Successful!</h5>
                            <div id="attendee-details"></div>
                        </div>
                        <button class="btn btn-primary" onclick="resetScanner()">
                            Scan Another Code
                        </button>
                    </div>

                    <div id="error-message" class="alert alert-danger d-none"></div>
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
        // Stop scanner
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop();
        }

        // Send token to server
        $.ajax({
            url: '{{ route("scanner.validate") }}',
            method: 'POST',
            data: {
                token: decodedText
            },
            success: function(response) {
                if (response.success) {
                    showScanResult(response.attendee);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON.message);
            }
        });
    }

    function showScanResult(attendee) {
        $('#scanner-container').addClass('d-none');
        $('#scan-result').removeClass('d-none');
        $('#error-message').addClass('d-none');
        
        const details = `
            <p><strong>Name:</strong> ${attendee.name}</p>
            <p><strong>Email:</strong> ${attendee.email}</p>
            <p><strong>Scanned at:</strong> ${new Date().toLocaleString()}</p>
        `;
        $('#attendee-details').html(details);
    }

    function showError(message) {
        $('#error-message').removeClass('d-none').text(message);
        setTimeout(() => {
            $('#error-message').addClass('d-none');
            startScanner();
        }, 3000);
    }

    function resetScanner() {
        $('#scan-result').addClass('d-none');
        $('#scanner-container').removeClass('d-none');
        startScanner();
    }

    function startScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }

        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: { width: 250, height: 250 } }
        );
        html5QrcodeScanner.render(onScanSuccess);
    }

    // Initialize scanner when page loads
    $(document).ready(function() {
        startScanner();
    });
</script>
@endpush
@endsection 