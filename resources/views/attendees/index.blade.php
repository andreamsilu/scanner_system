<x-app-layout>
    <x-page-header 
        title="Attendees"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Attendees' => null
        ]"
    >
        <x-slot name="actions">
            <div class="d-flex gap-2">
                <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Add Attendee
                </a>
                <a href="{{ route('events.attendees.bulk-upload.create', $event) }}" class="btn btn-success">
                    <i class="bi bi-upload me-2"></i>Bulk Upload
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('events.attendees.index', $event) }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name or email...">
                    </div>
                    <div class="col-md-3">
                        <label for="event_filter" class="form-label">Event</label>
                        <select class="form-select" id="event_filter" name="event_filter">
                            <option value="">All Events</option>
                            @foreach($events as $eventOption)
                                <option value="{{ $eventOption->id }}" {{ request('event_filter') == $eventOption->id ? 'selected' : '' }}>
                                    {{ $eventOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Registered</option>
                            <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendees Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable" id="attendeesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Event</th>
                                <th>Registration Status</th>
                                <th>Ticket Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendees as $attendee)
                                <tr>
                                    <td>
                                        {{ $attendee->name }}
                                        @if($attendee->phone)
                                            <br>
                                            <small class="text-muted">{{ $attendee->phone }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $attendee->email }}</td>
                                    <td>{{ $attendee->event->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                            {{ ucfirst($attendee->registration_status) }}
                                        </span>
                                        @if($attendee->registration_status === 'checked_in' && $attendee->checked_in_at)
                                            <br>
                                            <small class="text-muted">Checked in: {{ $attendee->checked_in_at->format('M j, Y g:i A') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendee->ticket)
                                            <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'active' ? 'primary' : 'danger') }}">
                                                {{ ucfirst($attendee->ticket->status) }}
                                            </span>
                                            @if($attendee->ticket->used_at)
                                                <br>
                                                <small class="text-muted">Used: {{ $attendee->ticket->used_at->format('M j, Y g:i A') }}</small>
                                            @endif
                                            <br>
                                            <small class="text-muted">Token: {{ $attendee->ticket->token }}</small>
                                        @else
                                            <span class="badge bg-secondary">No Ticket</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $attendee->created_at->format('M j, Y g:i A') }}
                                        @if($attendee->updated_at->diffInDays($attendee->created_at) > 0)
                                            <br>
                                            <small class="text-muted">Updated: {{ $attendee->updated_at->format('M j, Y g:i A') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('events.attendees.show', ['event' => $event, 'attendee' => $attendee]) }}" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('events.attendees.edit', ['event' => $event, 'attendee' => $attendee]) }}" 
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Edit Attendee">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($attendee->registration_status !== 'cancelled')
                                                <form action="{{ route('events.attendees.cancel', ['event' => $event, 'attendee' => $attendee]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to cancel this registration?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Cancel Registration">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                                            <p class="mb-0">No attendees found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($attendees->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $attendees->withQueryString()->links() }}
                    </div>
                @endif
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
                order: [[5, 'desc']], // Sort by registration date by default
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                columnDefs: [
                    { orderable: false, targets: [6] } // Disable sorting on actions column
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search attendees...",
                    emptyTable: "No attendees found."
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 