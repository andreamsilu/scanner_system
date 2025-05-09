<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">QR Scanner</h5>
                <button id="toggleCamera" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-camera"></i>
                    Toggle Camera
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="reader" class="w-100" style="height: 400px;"></div>
            <div class="mt-3">
                <div id="scan-result" class="alert d-none"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner = null;
        let isScanning = true;

        function initializeScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { 
                    fps: 10, 
                    qrbox: {width: 250, height: 250},
                    aspectRatio: 1.0
                },
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;
            
            html5QrcodeScanner.pause();
            isScanning = false;
            
            const resultDiv = document.getElementById('scan-result');
            resultDiv.className = 'alert alert-info';
            resultDiv.textContent = 'Processing scan...';
            resultDiv.classList.remove('d-none');

            fetch('{{ $scanUrl }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ $csrfToken }}'
                },
                body: JSON.stringify({ token: decodedText })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'alert alert-success';
                    resultDiv.textContent = data.message;
                    playSound('success');
                } else {
                    resultDiv.className = 'alert alert-danger';
                    resultDiv.textContent = data.message;
                    playSound('error');
                }
            })
            .catch(error => {
                resultDiv.className = 'alert alert-danger';
                resultDiv.textContent = 'Error processing scan. Please try again.';
                playSound('error');
            })
            .finally(() => {
                setTimeout(() => {
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.resume();
                        isScanning = true;
                    }
                    resultDiv.classList.add('d-none');
                }, 2000);
            });
        }

        function onScanFailure(error) {
            console.warn(`QR code scanning failed: ${error}`);
        }

        function playSound(type) {
            const audio = new Audio(`/sounds/${type}.mp3`);
            audio.play().catch(e => console.log('Audio play failed:', e));
        }

        initializeScanner();

        document.getElementById('toggleCamera').addEventListener('click', function() {
            if (html5QrcodeScanner) {
                if (isScanning) {
                    html5QrcodeScanner.pause();
                    isScanning = false;
                    this.innerHTML = '<i class="fas fa-play"></i> Start Camera';
                } else {
                    html5QrcodeScanner.resume();
                    isScanning = true;
                    this.innerHTML = '<i class="fas fa-pause"></i> Pause Camera';
                }
            }
        });
    </script>
    @endpush
</div> 