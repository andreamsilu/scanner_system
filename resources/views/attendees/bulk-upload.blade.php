<x-app-layout>
    <x-page-header 
        title="Bulk Upload Attendees"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Attendees' => route('events.attendees.index', $event),
            'Bulk Upload' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Attendees
            </a>
            <a href="{{ route('events.attendees.template') }}" class="btn btn-success me-2">
                <i class="bi bi-file-excel me-2"></i>Download Template
            </a>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-upload me-2"></i>Upload Attendees for {{ $event->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-2">Instructions</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-2">Please download the template file and fill it with your attendee information. The file should include the following columns:</p>
                                <ul class="list-disc list-inside mb-3">
                                    <li><strong>name</strong> - Full name of the attendee (required)</li>
                                    <li><strong>email</strong> - Email address (optional)</li>
                                    <li><strong>phone</strong> - Phone number (required, must be unique)</li>
                                    <li><strong>position</strong> - Job position or title (optional)</li>
                                    <li><strong>ticket_type</strong> - Type of ticket (required, must be one of: regular, vip, single, double)</li>
                                </ul>
                                <p class="text-muted small mb-0">The first row should contain the column headers exactly as shown above.</p>
                            </div>
                        </div>

                        <form action="{{ route('events.attendees.bulk-upload.store', $event) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                            @csrf
                            <div class="mb-4">
                                <label for="csv_file" class="form-label">CSV File</label>
                                <input type="file" 
                                       name="csv_file" 
                                       id="csv_file" 
                                       accept=".csv,.txt" 
                                       required
                                       class="form-control @error('csv_file') is-invalid @enderror">
                                @error('csv_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-2">Important Notes:</h6>
                                <ul class="mb-0 ps-3">
                                    <li>Each attendee will automatically receive a unique token and QR code</li>
                                    <li>The process may take some time depending on the number of records</li>
                                    <li>You will be notified when the upload is complete</li>
                                    <li>Maximum file size is 10MB</li>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="uploadButton">
                                    <i class="bi bi-upload me-2"></i>Upload and Process
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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