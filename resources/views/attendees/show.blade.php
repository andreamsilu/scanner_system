<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Attendee Details</h2>
            <div>
                <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" class="btn btn-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit Attendee
                </a>
                <a href="{{ route('events.attendees', $event) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Attendees
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Attendee Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Attendee Information</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Name</label>
                                <p class="mb-0">{{ $attendee->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Email</label>
                                <p class="mb-0">{{ $attendee->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Phone</label>
                                <p class="mb-0">{{ $attendee->phone ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Registration Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                        {{ ucfirst($attendee->registration_status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Registration Date</label>
                                <p class="mb-0">{{ $attendee->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Last Updated</label>
                                <p class="mb-0">{{ $attendee->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Information -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Ticket Information</h5>
                    @if($attendee->ticket)
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Ticket Number</label>
                                    <p class="mb-0">{{ $attendee->ticket->ticket_number }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Status</label>
                                    <p class="mb-0">
                                        <span class="badge bg-{{ $attendee->ticket->status === 'valid' ? 'success' : 'danger' }}">
                                            {{ ucfirst($attendee->ticket->status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Created At</label>
                                    <p class="mb-0">{{ $attendee->ticket->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Last Updated</label>
                                    <p class="mb-0">{{ $attendee->ticket->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div class="text-center mt-4">
                            <img src="{{ $attendee->ticket->qr_code_url }}" 
                                 alt="Ticket QR Code" 
                                 class="img-fluid" 
                                 style="max-width: 200px;">
                            <p class="text-muted mt-2 mb-0">Ticket QR Code</p>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-ticket-perforated-x fs-1 d-block mb-2"></i>
                                No ticket assigned to this attendee.
                            </div>
                            <a href="{{ route('events.tickets.create', [$event, $attendee]) }}" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle me-2"></i>Generate Ticket
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Scan History -->
            @if($attendee->ticket && $attendee->ticket->scan_logs && $attendee->ticket->scan_logs->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Scan History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Scanner</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendee->ticket->scan_logs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                            <td>{{ $log->scanner->name ?? 'System' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $log->status === 'valid' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($log->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $log->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Scan History</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                            No scan history available for this attendee.
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Event Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Event Information</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted">Event Name</label>
                        <p class="mb-0">{{ $event->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Date</label>
                        <p class="mb-0">
                            {{ $event->start_date->format('M d, Y') }} - 
                            {{ $event->end_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Venue</label>
                        <p class="mb-0">{{ $event->venue }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Location</label>
                        <p class="mb-0">{{ $event->location }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        @if($attendee->ticket)
                            <a href="{{ route('events.tickets.print', [$event, $attendee->ticket]) }}" 
                               class="btn btn-outline-primary"
                               target="_blank">
                                <i class="bi bi-printer me-2"></i>Print Ticket
                            </a>
                            <a href="{{ route('events.tickets.regenerate', [$event, $attendee->ticket]) }}" 
                               class="btn btn-outline-warning"
                               onclick="return confirm('Are you sure you want to regenerate this ticket? The old ticket will be invalidated.')">
                                <i class="bi bi-arrow-repeat me-2"></i>Regenerate Ticket
                            </a>
                        @endif
                        <button type="button" 
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteAttendeeModal">
                            <i class="bi bi-trash me-2"></i>Delete Attendee
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteAttendeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Attendee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this attendee? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('events.attendees.destroy', [$event, $attendee]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Attendee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 