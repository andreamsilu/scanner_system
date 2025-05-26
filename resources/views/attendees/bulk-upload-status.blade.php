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
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Processing Status</h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Status</dt>
                                            <dd class="col-sm-8">
                                                <span class="badge bg-{{ $bulkUpload->status === 'completed' ? 'success' : ($bulkUpload->status === 'failed' ? 'danger' : ($bulkUpload->status === 'processing' ? 'warning' : 'secondary')) }}">
                                                    {{ ucfirst($bulkUpload->status) }}
                                                </span>
                                            </dd>

                                            <dt class="col-sm-4 text-muted">Total Records</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->total_records }}</dd>

                                            <dt class="col-sm-4 text-muted">Processed</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->processed_records }}</dd>

                                            <dt class="col-sm-4 text-muted">Failed</dt>
                                            <dd class="col-sm-8">{{ $bulkUpload->failed_records }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($bulkUpload->error_message)
                            <div class="alert alert-danger mt-4">
                                <h6 class="alert-heading mb-2">Errors</h6>
                                <pre class="mb-0 text-danger">{{ $bulkUpload->error_message }}</pre>
                            </div>
                        @endif

                        @if($bulkUpload->status === 'completed' && $bulkUpload->attendees->isNotEmpty())
                            <div class="mt-4">
                                <h6 class="fw-semibold mb-3">Uploaded Attendees</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Position</th>
                                                <th>Token</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bulkUpload->attendees as $attendee)
                                                <tr>
                                                    <td>{{ $attendee->name }}</td>
                                                    <td>{{ $attendee->email }}</td>
                                                    <td>{{ $attendee->phone ?? '-' }}</td>
                                                    <td>{{ $attendee->position ?? '-' }}</td>
                                                    <td><code>{{ $attendee->token }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 