<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Bulk Upload Attendees</h2>
            <div>
                <a href="{{ route('events.attendees.template', $event) }}" class="btn btn-success me-2">
                    <i class="bi bi-file-excel me-2"></i>Download Template
                </a>
                <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary me-2">
                    <i class="bi bi-person-plus me-2"></i>Add Attendee
                </a>
                <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Event
                </a>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Instructions</h5>
                <p class="mb-0">Please download the template file and fill it with your attendee information. The file should include the following columns:</p>
                <ul class="mb-0 mt-2">
                    <li><strong>name</strong> - Full name of the attendee (required)</li>
                    <li><strong>email</strong> - Email address (optional)</li>
                    <li><strong>phone</strong> - Phone number (required, must be unique)</li>
                    <li><strong>position</strong> - Job position or title (optional)</li>
                    <li><strong>ticket_type</strong> - Type of ticket (required, must be one of: regular, vip, single, double)</li>
                </ul>
            </div>

            <form action="{{ route('events.attendees.bulk-upload.store', $event) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <div class="mb-3">
                    <label for="csv_file" class="form-label">Select File</label>
                    <input type="file" class="form-control @error('csv_file') is-invalid @enderror" id="csv_file" name="csv_file" accept=".xlsx,.xls,.csv" required>
                    @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Supported formats: Excel (.xlsx, .xls) and CSV (.csv). Maximum file size: 10MB.</div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" id="uploadButton">
                        <i class="bi bi-upload me-2"></i>Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = document.getElementById('uploadButton');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Uploading...';
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Upload failed. Please try again.');
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });
    </script>
    @endpush
</x-app-layout> 