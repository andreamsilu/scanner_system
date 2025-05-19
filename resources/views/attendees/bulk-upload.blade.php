@extends('layouts.app')

@section('title', 'Bulk Upload Attendees')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Bulk Upload Attendees</h2>
        <a href="{{ route('attendees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Attendees
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('attendees.process-bulk-upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="event_id" class="form-label">Select Event</label>
                            <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror" required>
                                <option value="">Choose an event...</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                        {{ $event->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="file" class="form-label">CSV File</label>
                            <input type="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   id="file" 
                                   name="file" 
                                   accept=".csv,.txt"
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                The CSV file should have the following columns in order: Name, Email, Phone (optional)
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Upload and Process
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Instructions</h5>
                </div>
                <div class="card-body">
                    <h6>CSV File Format</h6>
                    <p>Your CSV file should have the following columns in order:</p>
                    <ol>
                        <li>Name (required)</li>
                        <li>Email (required, must be unique)</li>
                        <li>Phone (optional)</li>
                    </ol>

                    <h6>Example:</h6>
                    <pre class="bg-light p-3 rounded">
Name,Email,Phone
John Doe,john@example.com,1234567890
Jane Smith,jane@example.com,0987654321</pre>

                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Duplicate email addresses will be skipped. The system will only add new attendees or update existing ones.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 