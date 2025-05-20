<x-app-layout>
    <x-slot name="header">
        <div class="py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Bulk Upload Attendees</h2>
                    <nav aria-label="breadcrumb" class="mt-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('attendees.index') }}">Attendees</a></li>
                            <li class="breadcrumb-item active">Bulk Upload</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('attendees.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Attendees
                </a>
            </div>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-upload me-2"></i>Upload Instructions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading mb-2">
                            <i class="bi bi-info-circle me-2"></i>File Requirements
                        </h6>
                        <ul class="mb-0">
                            <li>File must be in CSV format</li>
                            <li>Required columns: name, email, event_id</li>
                            <li>Optional columns: phone, notes</li>
                            <li>Maximum file size: 2MB</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading mb-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>Important Notes
                        </h6>
                        <ul class="mb-0">
                            <li>Duplicate email addresses will be skipped</li>
                            <li>Invalid event IDs will be skipped</li>
                            <li>You can download a template file below</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Upload File
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendees.bulk-upload.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">Select CSV File</label>
                            <input type="file" 
                                   class="form-control @error('csv_file') is-invalid @enderror" 
                                   id="csv_file" 
                                   name="csv_file" 
                                   accept=".csv"
                                   required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input @error('generate_tickets') is-invalid @enderror" 
                                       id="generate_tickets" 
                                       name="generate_tickets" 
                                       value="1" 
                                       {{ old('generate_tickets') ? 'checked' : '' }}>
                                <label class="form-check-label" for="generate_tickets">
                                    Generate tickets for all attendees
                                </label>
                                @error('generate_tickets')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('attendees.bulk-upload.template') }}" class="btn btn-outline-primary">
                                <i class="bi bi-download me-2"></i>Download Template
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Upload Attendees
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if(session('upload_results'))
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clipboard-check me-2"></i>Upload Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h6 class="alert-heading mb-2">Successfully Processed</h6>
                            <p class="mb-0">
                                {{ session('upload_results.success') }} attendees were successfully added.
                            </p>
                        </div>

                        @if(session('upload_results.errors'))
                            <div class="alert alert-danger">
                                <h6 class="alert-heading mb-2">Errors</h6>
                                <ul class="mb-0">
                                    @foreach(session('upload_results.errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(session('upload_results.skipped'))
                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-2">Skipped Entries</h6>
                                <ul class="mb-0">
                                    @foreach(session('upload_results.skipped') as $skip)
                                        <li>{{ $skip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // File size validation
            $('#csv_file').on('change', function() {
                const file = this.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (file && file.size > maxSize) {
                    alert('File size exceeds 2MB limit');
                    this.value = '';
                }

                // File type validation
                if (file && !file.name.endsWith('.csv')) {
                    alert('Please select a CSV file');
                    this.value = '';
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 