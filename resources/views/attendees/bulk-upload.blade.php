@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bulk Upload Attendees</h3>
                    <div class="card-tools">
                        <a href="{{ route('attendees.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Instructions</h5>
                                <p>Please upload a CSV file with the following columns:</p>
                                <ul>
                                    <li><strong>event_id</strong> - The ID of the event</li>
                                    <li><strong>name</strong> - Full name of the attendee</li>
                                    <li><strong>email</strong> - Email address</li>
                                    <li><strong>phone</strong> - Phone number (optional)</li>
                                    <li><strong>ticket_type_id</strong> - The ID of the ticket type</li>
                                    <li><strong>status</strong> - Status (registered, checked_in, cancelled)</li>
                                </ul>
                                <p>Download the <a href="{{ route('attendees.bulk-upload.template') }}" class="alert-link">template file</a> for reference.</p>
                            </div>

                            <form action="{{ route('attendees.bulk-upload.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="csv_file">CSV File</label>
                                    <input type="file" class="form-control-file @error('csv_file') is-invalid @enderror" 
                                           id="csv_file" name="csv_file" accept=".csv">
                                    @error('csv_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Uploads</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Records</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recentUploads ?? [] as $upload)
                                                    <tr>
                                                        <td>{{ $upload->created_at->format('M d, Y H:i') }}</td>
                                                        <td>{{ $upload->total_records }}</td>
                                                        <td>
                                                            @if($upload->status === 'completed')
                                                                <span class="badge badge-success">Completed</span>
                                                            @elseif($upload->status === 'failed')
                                                                <span class="badge badge-danger">Failed</span>
                                                            @else
                                                                <span class="badge badge-warning">Processing</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center">No recent uploads</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bulk Upload Attendees</h1>
        <a href="{{ route('attendees.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Attendees
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('attendees.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="event_id" class="form-label">Select Event</label>
                            <select class="form-select @error('event_id') is-invalid @enderror" id="event_id" name="event_id" required>
                                <option value="">Choose Event</option>
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
                            <label for="file" class="form-label">Upload CSV File</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Supported format: .csv</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Attendees
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Instructions</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">File Format Requirements:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            First row should contain headers
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Required columns: name, email, phone
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Optional columns: ticket_type, status
                        </li>
                    </ul>

                    <h6 class="mb-3 mt-4">Sample Format:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>name</th>
                                    <th>email</th>
                                    <th>phone</th>
                                    <th>ticket_type</th>
                                    <th>status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>John Doe</td>
                                    <td>john@example.com</td>
                                    <td>1234567890</td>
                                    <td>regular</td>
                                    <td>pending</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('attendees.download-template') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 