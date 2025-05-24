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
                            <option value="unchecked_in" {{ request('status') === 'unchecked_in' ? 'selected' : '' }}>Unchecked In</option>
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
                                <th>Phone</th>
                                <th>Registration Status</th>
                                <th>Ticket Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendees as $attendee)
                                <tr>
                                    <td>{{ $attendee->name }}</td>
                                    <td>{{ $attendee->phone ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'primary') }}">
                                            {{ $attendee->registration_status === 'unchecked_in' ? 'Unchecked In' : ucfirst($attendee->registration_status) }}
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
                                        @else
                                            <span class="badge bg-secondary">No Ticket</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $attendee->created_at->format('M j, Y g:i A') }}
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
                                            <form action="{{ route('events.attendees.destroy', ['event' => $event, 'attendee' => $attendee]) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this attendee? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete Attendee">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
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
    <style>
        .dt-buttons {
            margin-bottom: 1rem;
            float: left;
        }
        .dt-button {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            margin-right: 0.25rem;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        .dt-button:hover {
            background-color: #f8f9fa;
        }
        .dt-button-collection {
            width: auto !important;
            padding: 0.5rem;
        }
        .dt-button-collection .dt-button {
            margin: 0.25rem;
            width: calc(100% - 0.5rem);
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
            var table = $('#attendeesTable').DataTable({
                responsive: true,
                processing: true,
                pageLength: 10,
                order: [[4, 'desc']], // Sort by registration date by default
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-2"></i>Export',
                        className: 'btn btn-primary',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                                className: 'btn btn-success',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bi bi-file-earmark-text me-2"></i>CSV',
                                className: 'btn btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(doc) {
                                    doc.defaultStyle.fontSize = 10;
                                    doc.styles.tableHeader.fontSize = 11;
                                    doc.styles.tableHeader.fillColor = '#f8f9fa';
                                    doc.styles.tableHeader.color = '#212529';
                                    doc.pageMargins = [40, 60, 40, 60];
                                }
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer me-2"></i>Print',
                                className: 'btn btn-secondary',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(win) {
                                    $(win.document.body).css('font-size', '10pt');
                                    $(win.document.body).find('table')
                                        .addClass('compact')
                                        .css('font-size', 'inherit');
                                }
                            }
                        ]
                    }
                ],
                columnDefs: [
                    { 
                        orderable: false, 
                        targets: [5], // Disable sorting on actions column
                        searchable: false
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search attendees...",
                    emptyTable: "No attendees found.",
                    info: "Showing _START_ to _END_ of _TOTAL_ attendees",
                    infoEmpty: "Showing 0 to 0 of 0 attendees",
                    infoFiltered: "(filtered from _MAX_ total attendees)",
                    lengthMenu: "Show _MENU_ attendees per page",
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                }
            });

            // Ensure buttons are properly initialized
            table.buttons().container().addClass('float-start');
        });
    </script>
    @endpush
</x-app-layout> 