<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">{{ $event->name }}</h2>
            <div>
                <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit Event
                </a>
                <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Events
                </a>
            </div>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Event Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Venue</label>
                                <p class="mb-0 fw-medium">{{ $event->venue }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Location</label>
                                <p class="mb-0 fw-medium">{{ $event->location }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Capacity</label>
                                <p class="mb-0 fw-medium">{{ number_format($event->capacity) }} attendees</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Start Date</label>
                                <p class="mb-0 fw-medium">{{ $event->start_date->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small">End Date</label>
                                <p class="mb-0 fw-medium">{{ $event->end_date->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people me-2"></i>Attendees
                        </h5>
                        <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-people me-2"></i>Manage Attendees
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="attendeesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->attendees as $attendee)
                                    <tr>
                                        <td>{{ $attendee->name }}</td>
                                        <td>{{ $attendee->email }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                                {{ ucfirst($attendee->registration_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $attendee->registered_at ? $attendee->registered_at->format('M j, Y g:i A') : 'N/A' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('events.attendees.show', [$event, $attendee]) }}" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" 
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="Edit Attendee">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                                <p class="mb-0">No attendees registered yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Event Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }} me-2">
                            {{ ucfirst($event->status) }}
                        </span>
                        <span class="text-muted small">
                            {{ $event->status === 'active' ? 'Event is currently active' : 
                               ($event->status === 'draft' ? 'Event is in draft mode' : 'Event has been cancelled') }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Registration Status</label>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" 
                                 role="progressbar" 
                                 style="width: {{ ($event->attendees->count() / $event->capacity) * 100 }}%"
                                 aria-valuenow="{{ ($event->attendees->count() / $event->capacity) * 100 }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            {{ $event->attendees->count() }} of {{ $event->capacity }} spots filled
                        </small>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('events.tickets', $event) }}" class="btn btn-outline-primary">
                            <i class="bi bi-ticket-perforated me-2"></i>Manage Tickets
                        </a>
                        <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus me-2"></i>Add Attendee
                        </a>
                        <button type="button" 
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteEventModal">
                            <i class="bi bi-trash me-2"></i>Delete Event
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scanner Assignment Section -->
            @include('events.partials.scanner-assignment')
        </div>
    </div>

    <!-- Delete Event Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEventModalLabel">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Delete Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this event? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('events.destroy', $event) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Delete Event
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    @endpush

    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#attendeesTable').DataTable({
                responsive: true,
                processing: true,
                pageLength: 10,
                order: [[3, 'desc']], // Sort by registration date by default
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    { orderable: false, targets: [4] } // Disable sorting on actions column
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search attendees...",
                    emptyTable: "No attendees registered yet."
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 