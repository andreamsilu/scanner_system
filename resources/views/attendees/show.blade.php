<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center py-3">
            <div>
                <h2 class="mb-0">Attendee Details</h2>
                <nav aria-label="breadcrumb" class="mt-2">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Events</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.attendees.index', $event) }}">Attendees</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Edit Attendee
                </a>
                <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Attendees
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Attendee Information -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-circle me-2"></i>Attendee Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Name</label>
                                <p class="mb-0 fw-medium">{{ $attendee->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Email</label>
                                <p class="mb-0 fw-medium">{{ $attendee->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Phone</label>
                                <p class="mb-0 fw-medium">{{ $attendee->phone ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Registration Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                        {{ ucfirst($attendee->registration_status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Registration Date</label>
                                <p class="mb-0 fw-medium">{{ $attendee->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Last Updated</label>
                                <p class="mb-0 fw-medium">{{ $attendee->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Information -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-ticket-perforated me-2"></i>Ticket Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($attendee->ticket)
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Ticket Number</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->ticket->ticket_number }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Status</label>
                                    <p class="mb-0">
                                        <span class="badge bg-{{ $attendee->ticket->status === 'valid' ? 'success' : 'danger' }}">
                                            {{ ucfirst($attendee->ticket->status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Created At</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->ticket->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Last Updated</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->ticket->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <img src="{{ $attendee->ticket->qr_code_url }}" 
                                 alt="Ticket QR Code" 
                                 class="img-fluid rounded shadow-sm" 
                                 style="max-width: 200px;">
                            <p class="text-muted mt-2 mb-0 small">Ticket QR Code</p>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-ticket-perforated-x fs-1 d-block mb-3"></i>
                                <p class="mb-3">No ticket assigned to this attendee.</p>
                            </div>
                            <a href="{{ route('events.tickets.create', [$event, $attendee]) }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Generate Ticket
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Scan History -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Scan History
                    </h5>
                </div>
                <div class="card-body">
                    @if($attendee->ticket && $attendee->ticket->scan_logs && $attendee->ticket->scan_logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
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
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-clock-history fs-1 d-block mb-3"></i>
                            <p class="mb-0">No scan history available for this attendee.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Event Information and Quick Actions -->
        <div class="col-md-4">
            <!-- Event Information -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-event me-2"></i>Event Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Event Name</label>
                        <p class="mb-0 fw-medium">{{ $event->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Date</label>
                        <p class="mb-0 fw-medium">
                            {{ $event->start_date->format('M d, Y') }} - 
                            {{ $event->end_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Venue</label>
                        <p class="mb-0 fw-medium">{{ $event->venue }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Location</label>
                        <p class="mb-0 fw-medium">{{ $event->location }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($attendee->ticket)
                            <a href="{{ route('events.tickets.print', [$event, $attendee->ticket]) }}" 
                               class="btn btn-outline-primary"
                               target="_blank">
                                <i class="bi bi-printer me-2"></i>Print Ticket
                            </a>
                            @if($attendee->ticket->status === 'valid')
                                <form action="{{ route('events.tickets.invalidate', [$event, $attendee->ticket]) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to invalidate this ticket? This action cannot be undone.')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-x-circle me-2"></i>Invalidate Ticket
                                    </button>
                                </form>
                            @endif
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
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Delete Attendee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this attendee? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('events.attendees.destroy', [$event, $attendee]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Delete Attendee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 