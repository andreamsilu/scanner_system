<x-app-layout>
    <x-page-header 
        title="Bulk Upload Status"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $bulkUpload->event->name => route('events.show', $bulkUpload->event),
            'Attendees' => route('events.attendees.index', $bulkUpload->event),
            'Upload Status' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.attendees.index', $bulkUpload->event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Attendees
            </a>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Upload Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Upload Information</h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Event</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->event->name }}</dd>

                                            <dt class="col-sm-4 text-muted">File Name</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->filename }}</dd>

                                            <dt class="col-sm-4 text-muted">Uploaded By</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->user->name }}</dd>

                                            <dt class="col-sm-4 text-muted">Upload Date</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->created_at->format('M d, Y H:i:s') }}</dd>

                                            <dt class="col-sm-4 text-muted">Status</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge bg-{{ $bulkUpload->status === 'completed' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($bulkUpload->status) }}
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($bulkUpload->error_log)
                            <div class="alert alert-danger mt-4">
                                <h6 class="alert-heading mb-2">Errors Found</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>Error</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(json_decode($bulkUpload->error_log, true) as $error)
                                                <tr>
                                                    <td>{{ $error['row'] }}</td>
                                                    <td>{{ $error['error'] }}</td>
                                                    <td>
                                                        <small class="text-muted">
                                                            @foreach($error['data'] as $key => $value)
                                                                <strong>{{ $key }}:</strong> {{ $value }}<br>
                                                            @endforeach
                                                        </small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if($bulkUpload->status === 'completed')
                            <div class="alert alert-success mt-4">
                                <i class="bi bi-check-circle me-2"></i>
                                All attendees were successfully uploaded.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 