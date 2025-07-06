<x-app-layout>
    <x-page-header 
        :title="$event->name . ' - Tickets'"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Tickets' => null
        ]"
    >
        <x-slot name="actions">
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#generateTicketsModal">
                <i class="bi bi-plus-lg me-2"></i>Generate Tickets
            </button>
            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Event
            </a>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-tag"></i> Ticket Type</label>
                    <select class="form-select" id="ticketTypeFilter">
                        <option value=""><i class="bi bi-funnel"></i> All Types</option>
                        <option value="vip"><i class="bi bi-star"></i> VIP</option>
                        <option value="regular"><i class="bi bi-ticket"></i> Regular</option>
                        <option value="single"><i class="bi bi-person"></i> Single</option>
                        <option value="double"><i class="bi bi-people"></i> Double</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-toggle-on"></i> Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value=""><i class="bi bi-funnel"></i> All Status</option>
                        <option value="active"><i class="bi bi-check-circle"></i> Active</option>
                        <option value="inactive"><i class="bi bi-x-circle"></i> Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-calendar-range"></i> Date Range</label>
                    <input type="text" class="form-control" id="dateRange" placeholder="Select date range">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" id="applyFilters">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="bulk-actions" style="display: none;">
                    <button class="btn btn-warning me-2" id="bulkDeactivate">
                        <i class="bi bi-x-circle"></i> Deactivate Selected
                    </button>
                    <button class="btn btn-success me-2" id="bulkActivate">
                        <i class="bi bi-check-circle"></i> Activate Selected
                    </button>
                    <button class="btn btn-danger" id="bulkDelete">
                        <i class="bi bi-trash"></i> Delete Selected
                    </button>
                </div>
                <div class="selected-count" style="display: none;">
                    <span class="badge bg-primary">0</span> tickets selected
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tickets-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th><i class="bi bi-upc-scan"></i> Ticket ID</th>
                            <th><i class="bi bi-person"></i> Attendee</th>
                            <th><i class="bi bi-tag"></i> Type</th>
                            <th><i class="bi bi-toggle-on"></i> Status</th>
                            <th><i class="bi bi-clock-history"></i> Last Scan</th>
                            <th><i class="bi bi-calendar-plus"></i> Created At</th>
                            <th><i class="bi bi-gear"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input ticket-checkbox" 
                                           value="{{ $ticket->id }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $ticket->ticket_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($ticket->attendee)
                                        {{ $ticket->attendee->token }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $ticket->type }}
                                </td>
                                <td>
                                    @if($ticket->is_active)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->scanLogs->isNotEmpty())
                                        <span title="{{ $ticket->scanLogs->first()->created_at->format('F j, Y g:i A') }}">
                                            <i class="bi bi-clock-history"></i> {{ $ticket->scanLogs->first()->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted"><i class="bi bi-clock"></i> Never</span>
                                    @endif
                                </td>
                                <td><i class="bi bi-calendar-plus"></i> {{ $ticket->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <div class="btn-group">
                                        @if($ticket->attendee && $ticket->attendee->token)
                                            <a href="{{ route('events.tickets.show', ['event' => $event->id, 'ticket' => $ticket->attendee->token]) }}" 
                                               class="text-indigo-600 hover:text-indigo-900"
                                               title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('events.tickets.print', ['event' => $event->id, 'ticket' => $ticket->attendee->token]) }}" 
                                               class="text-green-600 hover:text-green-900"
                                               title="Download Ticket"
                                               target="_blank">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if($ticket->is_active)
                                                <form action="{{ route('events.tickets.deactivate', ['event' => $event->id, 'ticket' => $ticket->attendee->token]) }}" 
                                                      method="POST" 
                                                      class="inline-block"
                                                      onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-warning" 
                                                            title="Deactivate">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted" title="No attendee assigned">
                                                <i class="bi bi-person-x"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No tickets found for this event.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Generate Tickets Modal -->
@include('tickets.partials.generate-modal')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
    .system-logo {
        object-fit: contain;
        transition: transform 0.2s ease-in-out;
    }
    .system-logo:hover {
        transform: scale(1.05);
    }
    @media (max-width: 768px) {
        .system-logo {
            height: 40px !important;
        }
    }
</style>
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
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DateRangePicker
    $('#dateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        }
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Initialize DataTable with server-side processing
    const table = $('#tickets-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 15,
        order: [[6, 'desc']], // Default sort by created_at column in descending order
        orderCellsTop: true, // Enable ordering on header cells
        ajax: {
            url: "{{ route('events.tickets.data', $event) }}",
            type: 'GET',
            data: function(d) {
                // Add custom filters to the request
                d.ticket_type = $('#ticketTypeFilter').val();
                d.status = $('#statusFilter').val();
                d.date_range = $('#dateRange').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable error:', error);
                // Show error message to user
                alert('An error occurred while loading the data. Please try again.');
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'select-checkbox',
                render: function(data, type, row) {
                    return `<input type="checkbox" class="form-check-input ticket-checkbox" value="${row.id}">`;
                }
            },
            {
                data: 'ticket_number',
                name: 'ticket_number',
                orderable: true,
                searchable: true,
                render: function(data, type, row) {
                    if (type === 'sort') return data; // Return raw data for sorting
                    return `<span class="text-gray-500">${data}</span>`;
                }
            },
            {
                data: 'attendee',
                name: 'attendee',
                orderable: true,
                searchable: true,
                render: function(data, type, row) {
                    if (type === 'sort') return data ? data.token : ''; // Return token for sorting
                    if (!data) return '<span class="text-muted">Unassigned</span>';
                    return `<span class="text-gray-500">${data.token}</span>`;
                }
            },
            {
                data: 'type',
                name: 'type',
                orderable: true,
                searchable: true,
                render: function(data, type, row) {
                    if (type === 'sort') return data; // Return raw data for sorting
                    return `<span class="text-gray-500">${data}</span>`;
                }
            },
            {
                data: 'status',
                name: 'status',
                orderable: true,
                searchable: true,
                render: function(data, type, row) {
                    if (type === 'sort') return data.value; // Return status value for sorting
                    const badgeClass = data.badge === 'success' ? 'bg-success' : 
                                     data.badge === 'info' ? 'bg-info' : 'bg-danger';
                    return `<span class="badge ${badgeClass}">
                        <i class="bi bi-${data.badge === 'success' ? 'check-circle' : 
                                        data.badge === 'info' ? 'info-circle' : 'x-circle'}"></i> 
                        ${data.value}
                    </span>`;
                }
            },
            {
                data: 'last_scan',
                name: 'last_scan',
                orderable: true,
                searchable: false,
                render: function(data, type, row) {
                    if (type === 'sort') return data ? data.time : ''; // Return timestamp for sorting
                    if (!data) return '<span class="text-muted"><i class="bi bi-clock"></i> Never</span>';
                    return `<span title="${data.time}">
                        <i class="bi bi-clock-history"></i> ${data.diff}
                    </span>`;
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                orderable: true,
                searchable: false,
                render: function(data, type, row) {
                    if (type === 'sort') return data; // Return raw timestamp for sorting
                    return `<i class="bi bi-calendar-plus"></i> ${moment(data).format('MMM j, YYYY g:i A')}`;
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function(data, type, row) {
                    let html = '<div class="btn-group">';
                    
                    if (data.view_url) {
                        html += `<a href="${data.view_url}" class="text-indigo-600 hover:text-indigo-900" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>`;
                    }
                    
                    if (data.print_url) {
                        html += `<a href="${data.print_url}" class="text-green-600 hover:text-green-900" title="Download Ticket" target="_blank">
                            <i class="bi bi-download"></i>
                        </a>`;
                    }
                    
                    if (data.deactivate_url) {
                        html += `<form action="${data.deactivate_url}" method="POST" class="inline-block" 
                            onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-warning" title="Deactivate">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>`;
                    }
                    
                    html += '</div>';
                    return html;
                }
            }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="bi bi-download"></i> Export',
                className: 'btn btn-primary',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6],
                            orthogonal: 'sort' // Use sort data for export
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6],
                            orthogonal: 'sort' // Use sort data for export
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-info',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6],
                            orthogonal: 'sort' // Use sort data for export
                        }
                    }
                ]
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search tickets...",
            emptyTable: "No tickets found for this event.",
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            info: "Showing _START_ to _END_ of _TOTAL_ tickets",
            infoEmpty: "No tickets available",
            infoFiltered: "(filtered from _MAX_ total tickets)",
            lengthMenu: "Show _MENU_ tickets per page",
            paginate: {
                first: '<i class="bi bi-chevron-double-left"></i>',
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>'
            }
        },
        drawCallback: function(settings) {
            // Update bulk actions after each draw
            updateBulkActions();
        }
    });

    // Handle Select All Checkbox
    $('#selectAll').change(function() {
        $('.ticket-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });

    // Handle Individual Checkboxes
    $(document).on('change', '.ticket-checkbox', function() {
        updateBulkActions();
    });

    // Update Bulk Actions Visibility
    function updateBulkActions() {
        const selectedCount = $('.ticket-checkbox:checked').length;
        $('.selected-count span').text(selectedCount);
        $('.bulk-actions, .selected-count').toggle(selectedCount > 0);
    }

    // Apply Filters
    $('#applyFilters').click(function() {
        table.ajax.reload();
    });

    // Bulk Actions
    $('#bulkDeactivate').click(function() {
        if (confirm('Are you sure you want to deactivate selected tickets?')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk deactivate logic here
            console.log('Deactivating tickets:', selectedIds);
        }
    });

    $('#bulkActivate').click(function() {
        if (confirm('Are you sure you want to activate selected tickets?')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk activate logic here
            console.log('Activating tickets:', selectedIds);
        }
    });

    $('#bulkDelete').click(function() {
        if (confirm('Are you sure you want to delete selected tickets? This action cannot be undone.')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk delete logic here
            console.log('Deleting tickets:', selectedIds);
        }
    });
});
</script>
@endpush
</x-app-layout> 