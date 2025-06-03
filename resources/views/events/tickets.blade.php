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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="tickets-table">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th class="py-2">Ticket ID</th>
                            <th class="py-2">Attendee</th>
                            <th class="py-2">Type</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Last Scan</th>
                            <th class="py-2">Created</th>
                            <th class="py-2 text-end">Actions</th>
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
                                               title="Print Ticket"
                                               target="_blank">
                                                <i class="bi bi-printer"></i>
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
    // Initialize DataTable
    const table = $('#tickets-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("events.tickets.data", $event) }}',
            type: 'GET'
        },
        columns: [
            { 
                data: null,
                name: 'checkbox',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `<input type="checkbox" class="form-check-input ticket-checkbox" value="${row.id}">`;
                }
            },
            { 
                data: 'token',
                name: 'token',
                render: function(data) {
                    return `<span class="text-muted">#${data}</span>`;
                }
            },
            { 
                data: 'attendee',
                name: 'attendee',
                render: function(data) {
                    if (data) {
                        return `<div class="d-flex flex-column">
                            <span class="fw-medium">${data.name}</span>
                            ${data.phone ? `<small class="text-muted">${data.phone}</small>` : ''}
                        </div>`;
                    }
                    return '<span class="text-muted">Unassigned</span>';
                }
            },
            { 
                data: 'type',
                name: 'type',
                render: function(data) {
                    const badgeClass = {
                        'vip': 'bg-warning',
                        'regular': 'bg-secondary',
                        'single': 'bg-primary',
                        'double': 'bg-success'
                    }[data] || 'bg-secondary';
                    return `<span class="badge ${badgeClass} px-2 py-1">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'status',
                name: 'status',
                render: function(data) {
                    const badgeClass = data === 'active' ? 'bg-success' : 'bg-danger';
                    const statusText = data === 'active' ? 'Active' : 'Inactive';
                    return `<span class="badge ${badgeClass} px-2 py-1">${statusText}</span>`;
                }
            },
            { 
                data: 'last_scan',
                name: 'last_scan',
                render: function(data) {
                    return data ? `<span class="text-muted" title="${data.created_at}">${data.diff_for_humans}</span>` : '<span class="text-muted">Never</span>';
                }
            },
            { 
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return `<span class="text-muted">${data}</span>`;
                }
            },
            { 
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="/events/${row.event.id}/tickets/${row.token}" 
                               class="btn btn-outline-info" 
                               title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    title="Print Ticket"
                                    onclick="printTicket('/events/${row.event.id}/tickets/${row.token}/print')">
                                <i class="bi bi-printer"></i>
                            </button>
                            ${row.attendee && row.attendee.phone ? `
                                <button type="button"
                                        class="btn btn-outline-success send-invitation-btn"
                                        title="Send Invitation SMS"
                                        data-bs-toggle="modal"
                                        data-bs-target="#sendInvitationModal"
                                        data-ticket-id="${row.id}"
                                        data-attendee-name="${row.attendee.name}"
                                        data-attendee-phone="${row.attendee.phone}"
                                        data-attendee-id="${row.attendee.id}"
                                        data-event-name="${row.event.name}">
                                    <i class="bi bi-envelope"></i>
                                </button>
                            ` : ''}
                            <button type="button" 
                                    class="btn btn-outline-success" 
                                    title="Share Ticket"
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="bi bi-share"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="shareViaWhatsApp('${row.event.name}', '${row.attendee ? row.attendee.name : ''}', '${row.token}', '${row.event.start_date}', '${row.verify_url}')">
                                        <i class="fab fa-whatsapp me-2"></i> WhatsApp
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="shareViaSMS('${row.event.name}', '${row.attendee ? row.attendee.name : ''}', '${row.token}', '${row.event.start_date}', '${row.verify_url}')">
                                        <i class="fas fa-sms me-2"></i> SMS
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="copyTicketLink('${row.verify_url}')">
                                        <i class="fas fa-link me-2"></i> Copy Link
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/events/${row.event.id}/tickets/${row.token}/export" target="_blank">
                                        <i class="bi bi-file-pdf me-2"></i> Download PDF
                                    </a>
                                </li>
                            </ul>
                            ${row.is_active ? `
                                <form action="/events/${row.event.id}/tickets/${row.token}/deactivate" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="btn btn-outline-warning" 
                                            title="Deactivate">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ],
        order: [[6, 'desc']], // Sort by created_at by default
        pageLength: 20,
        dom: '<"row mb-2"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="bi bi-download"></i> Export',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-info',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    }
                ]
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search tickets...",
            lengthMenu: "_MENU_ per page",
            info: "Showing _START_ to _END_ of _TOTAL_ tickets",
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Handle Select All Checkbox
    $('#selectAll').change(function() {
        $('.ticket-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });

    // Handle Individual Checkboxes
    $('.ticket-checkbox').change(function() {
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
        const typeFilter = $('#ticketTypeFilter').val();
        const statusFilter = $('#statusFilter').val();
        const dateRange = $('#dateRange').val();

        table.column(3).search(typeFilter);
        table.column(4).search(statusFilter);
        
        if (dateRange) {
            const dates = dateRange.split(' - ');
            table.draw();
        }

        table.draw();
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
});
</script>
@endpush
</x-app-layout> 