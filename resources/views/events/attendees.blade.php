<x-app-layout>
    <x-page-header 
        title="Events"
        :breadcrumbs="['Events' => route('events.index'), $event->name => route('events.show', $event), 'Attendees' => null]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.attendees.template', $event) }}" class="btn btn-success me-2">
                <i class="bi bi-file-excel me-2"></i>Download Template
            </a>
            <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary me-2">
                <i class="bi bi-person-plus me-2"></i>Add Attendee
            </a>
            <a href="{{ route('events.attendees.bulk-upload', $event) }}" class="btn btn-info me-2">
                <i class="bi bi-upload me-2"></i>Bulk Upload
            </a>
            <a href="{{ route('events.attendees.export', $event) }}" class="btn btn-warning">
                <i class="bi bi-file-excel me-2"></i>Export Attendees
            </a>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bulk Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex gap-3">
                <a href="{{ route('events.attendees.template', $event) }}" class="btn btn-success">
                    <i class="bi bi-file-excel me-2"></i>Download Template
                </a>
                <a href="{{ route('events.attendees.bulk-upload', $event) }}" class="btn btn-primary">
                    <i class="bi bi-upload me-2"></i>Bulk Upload
                </a>
                <!-- Test button to verify button display -->
                <a href="#" class="btn btn-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>Test Button
                </a>
            </div>
            <!-- Debug info -->
            <div class="mt-3">
                <small class="text-muted">
                    Route exists: {{ route_exists('events.attendees.template') ? 'Yes' : 'No' }}
                </small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('events.attendees.index', $event) }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name or email...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Registration Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Registered</option>
                        <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="ticket_status" class="form-label">Ticket Status</label>
                    <select class="form-select" id="ticket_status" name="ticket_status">
                        <option value="">All Tickets</option>
                        <option value="valid" {{ request('ticket_status') === 'valid' ? 'selected' : '' }}>Valid</option>
                        <option value="invalid" {{ request('ticket_status') === 'invalid' ? 'selected' : '' }}>Invalid</option>
                        <option value="none" {{ request('ticket_status') === 'none' ? 'selected' : '' }}>No Ticket</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendees Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Status</th>
                            <th>Ticket Status</th>
                            <th>Registered At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendees as $attendee)
                            <tr>
                                <td>{{ $attendee->name }}</td>
                                <td>{{ $attendee->email }}</td>
                                <td>{{ $attendee->phone }}</td>
                                <td>
                                    <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                        {{ ucfirst($attendee->registration_status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($attendee->ticket)
                                        <span class="badge bg-{{ $attendee->ticket->status === 'valid' ? 'success' : 'danger' }}">
                                            {{ ucfirst($attendee->ticket->status) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">No Ticket</span>
                                    @endif
                                </td>
                                <td>{{ $attendee->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('events.attendees.show', [$event, $attendee]) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteAttendeeModal{{ $attendee->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteAttendeeModal{{ $attendee->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete Attendee</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete this attendee? This action cannot be undone.</p>
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-3">Name:</dt>
                                                        <dd class="col-sm-9">{{ $attendee->name }}</dd>
                                                        <dt class="col-sm-3">Email:</dt>
                                                        <dd class="col-sm-9">{{ $attendee->email }}</dd>
                                                        <dt class="col-sm-3">Phone:</dt>
                                                        <dd class="col-sm-9">{{ $attendee->phone }}</dd>
                                                    </dl>
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-people fa-3x mb-3"></i>
                                        <p class="mb-0">No attendees found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $attendees->links() }}
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }
    </style>
    @endpush

    @push('scripts')
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
            $('.datatable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[5, 'desc']], // Sort by registration date by default
                columnDefs: [
                    { orderable: false, targets: [6] } // Disable sorting on actions column
                ],
                language: {
                    emptyTable: "No attendees found for this event."
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 