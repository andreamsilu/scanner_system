<x-app-layout>
    <x-page-header 
        title="Tickets"
        :breadcrumbs="[
            'Events' => route('events.index'),
            'Tickets' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Events
            </a>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <form id="filter-form" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="event_filter" class="form-label">Event</label>
            <select class="form-select form-select-sm" id="event_filter" name="event_id">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                        {{ $event->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="type_filter" class="form-label">Ticket Type</label>
            <select class="form-select form-select-sm" id="type_filter" name="type">
                <option value="">All Types</option>
                <option value="vip" {{ request('type') == 'vip' ? 'selected' : '' }}>VIP</option>
                <option value="regular" {{ request('type') == 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="single" {{ request('type') == 'single' ? 'selected' : '' }}>Single</option>
                <option value="double" {{ request('type') == 'double' ? 'selected' : '' }}>Double</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="status_filter" class="form-label">Status</label>
            <select class="form-select form-select-sm" id="status_filter" name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="date_from" class="form-label">Date From</label>
            <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" 
                   value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label for="date_to" class="form-label">Date To</label>
            <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" 
                   value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-search"></i> Filter
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover table-sm" id="tickets-table">
            <thead class="table-light">
                <tr>
                    <th class="py-2">Attendee</th>
                    <th class="py-2">Event</th>
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
                        <td class="py-2">
                            @if($ticket->attendee)
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">{{ $ticket->attendee->name }}</span>
                                    <small class="text-muted">#{{ $ticket->token }}</small>
                                </div>
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td class="py-2">
                            <a href="{{ route('events.show', $ticket->event) }}" class="text-decoration-none">
                                {{ Str::limit($ticket->event->name, 20) }}
                            </a>
                        </td>
                        <td class="py-2">
                            @php
                                $ticketType = $ticket->type;
                                $badgeClass = match($ticketType) {
                                    'vip' => 'bg-warning',
                                    'regular' => 'bg-secondary',
                                    'single' => 'bg-primary',
                                    'double' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} px-2 py-1">
                                {{ ucfirst($ticketType) }}
                            </span>
                        </td>
                        <td class="py-2">
                            @if($ticket->is_active)
                                <span class="badge bg-success px-2 py-1">Active</span>
                            @else
                                <span class="badge bg-danger px-2 py-1">Inactive</span>
                            @endif
                        </td>
                        <td class="py-2">
                            @if($ticket->last_scan)
                                <span class="text-muted" title="{{ $ticket->last_scan->created_at->format('d-m-Y H:i') }}">
                                    {{ $ticket->last_scan->created_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                        <td class="py-2">
                            <span class="text-muted">{{ $ticket->created_at->format('d-m-Y') }}</span>
                        </td>
                        <td class="py-2 text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('events.events.tickets.show', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" 
                                   class="btn btn-outline-info" 
                                   title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-outline-primary"
                                        title="Print Ticket"
                                        onclick="printTicket('{{ route('events.events.tickets.print', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}')">
                                    <i class="bi bi-printer"></i>
                                </button>
                                @if($ticket->attendee && $ticket->attendee->phone)
                                    <button type="button"
                                            class="btn btn-outline-success send-invitation-btn"
                                            title="Send Invitation SMS"
                                            data-bs-toggle="modal"
                                            data-bs-target="#sendInvitationModal"
                                            data-ticket-id="{{ $ticket->id }}"
                                            data-attendee-name="{{ $ticket->attendee->name }}"
                                            data-attendee-phone="{{ $ticket->attendee->phone }}"
                                            data-attendee-id="{{ $ticket->attendee->id }}"
                                            data-event-name="{{ $ticket->event->name }}">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                @endif
                                <button type="button" 
                                        class="btn btn-outline-success" 
                                        title="Share Ticket"
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false">
                                    <i class="bi bi-share"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="shareViaWhatsApp('{{ $ticket->event->name }}', '{{ $ticket->attendee->name }}', '{{ $ticket->token }}', '{{ $ticket->event->start_date->format('M d, Y') }}', '{{ route('tickets.verify', $ticket->token) }}')">
                                            <i class="fab fa-whatsapp me-2"></i> WhatsApp
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="shareViaSMS('{{ $ticket->event->name }}', '{{ $ticket->attendee->name }}', '{{ $ticket->token }}', '{{ $ticket->event->start_date->format('M d, Y') }}', '{{ route('tickets.verify', $ticket->token) }}')">
                                            <i class="fas fa-sms me-2"></i> SMS
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="copyTicketLink('{{ route('tickets.verify', $ticket->token) }}')">
                                            <i class="fas fa-link me-2"></i> Copy Link
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('events.events.tickets.export', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" target="_blank">
                                            <i class="bi bi-file-pdf me-2"></i> Download PDF
                                        </a>
                                    </li>
                                </ul>
                                @if($ticket->is_active)
                                    <form action="{{ route('tickets.deactivate', $ticket) }}" 
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
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3">No tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small pagination-info">
            Showing {{ $tickets->firstItem() ?? 0 }} to {{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} entries
        </div>
        <div>
            {{ $tickets->links() }}
        </div>
    </div>

    @push('styles')
    <style>
        @media print {
            .table {
                font-size: 11px;
            }
            .btn-group {
                display: none !important;
            }
            .table td, .table th {
                padding: 0.25rem !important;
            }
            .badge {
                font-size: 10px;
            }
        }

        .table-sm td, .table-sm th {
            padding: 0.5rem;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }

        .badge {
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .dropdown-menu {
            font-size: 0.875rem;
            min-width: 180px;
        }

        .pagination {
            margin-bottom: 0;
        }
        .pagination .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Hide DataTables info and pagination */
        .dataTables_info, 
        .dataTables_paginate,
        .dataTables_length,
        .dataTables_filter,
        div.dataTables_wrapper div.dataTables_info,
        div.dataTables_wrapper div.dataTables_paginate,
        div.dataTables_wrapper div.dataTables_length,
        div.dataTables_wrapper div.dataTables_filter {
            display: none !important;
        }

        /* Ensure Laravel pagination is visible */
        .pagination {
            display: flex !important;
            margin-bottom: 0;
        }

        /* Filter form styles */
        .filter-form {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .form-select-sm, .form-control-sm {
            font-size: 0.875rem;
        }

        /* DataTables search box styles */
        .dataTables_filter {
            margin-bottom: 1rem;
            float: right;
        }

        .dataTables_filter input {
            margin-left: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            width: 300px;
            font-size: 0.875rem;
        }

        .dataTables_filter input:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .dataTables_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            font-weight: 500;
        }

        .dataTables_filter label::before {
            content: '\F52A';
            font-family: 'bootstrap-icons';
            margin-right: 0.5rem;
            color: #6c757d;
        }

        /* Table styles */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,.05);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#tickets-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: window.location.href,
                    type: 'GET',
                    data: function(d) {
                        // Add filter parameters
                        d.event_id = $('#event_filter').val();
                        d.type = $('#type_filter').val();
                        d.status = $('#status_filter').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                        
                        // Add pagination and ordering parameters
                        d.page = (d.start / d.length) + 1;
                        d.order = d.order[0] ? {
                            column: d.order[0].column,
                            dir: d.order[0].dir
                        } : null;
                    },
                    dataSrc: function(json) {
                        $('.pagination-info').html(
                            `Showing ${json.pagination.first_item} to ${json.pagination.last_item} of ${json.pagination.total} entries`
                        );
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 'attendee',
                        render: function(data) {
                            if (!data) return '<span class="text-muted">Unassigned</span>';
                            return `<div class="d-flex flex-column">
                                <span class="fw-medium">${data.name}</span>
                                <small class="text-muted">#${data.token}</small>
                            </div>`;
                        }
                    },
                    { 
                        data: 'event',
                        render: function(data) {
                            return `<a href="${data.url}" class="text-decoration-none">${data.name}</a>`;
                        }
                    },
                    { 
                        data: 'type',
                        render: function(data) {
                            return `<span class="badge ${data.badge_class} px-2 py-1">${data.type}</span>`;
                        }
                    },
                    { 
                        data: 'status',
                        render: function(data) {
                            return `<span class="badge ${data.badge_class} px-2 py-1">${data.status}</span>`;
                        }
                    },
                    { 
                        data: 'last_scan',
                        render: function(data) {
                            if (!data) return '<span class="text-muted">Never</span>';
                            return `<span class="text-muted" title="${data.full_time}">${data.time}</span>`;
                        }
                    },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return `<span class="text-muted">${data}</span>`;
                        }
                    },
                    { 
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            let buttons = `
                                <div class="btn-group btn-group-sm">
                                    <a href="${data.view_url}" class="btn btn-outline-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" title="Print Ticket" 
                                            onclick="printTicket('${data.print_url}')">
                                        <i class="bi bi-printer"></i>
                                    </button>`;
                            
                            if (data.has_phone) {
                                buttons += `
                                    <button type="button" class="btn btn-outline-success send-invitation-btn"
                                            title="Send Invitation SMS" data-bs-toggle="modal"
                                            data-bs-target="#sendInvitationModal">
                                        <i class="bi bi-envelope"></i>
                                    </button>`;
                            }
                            
                            buttons += `
                                <button type="button" class="btn btn-outline-success" title="Share Ticket"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-share"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="shareViaWhatsApp('${data.event_name}', '${data.attendee_name}', '${data.token}', '${data.event_date}', '${data.ticket_url}')">
                                        <i class="fab fa-whatsapp me-2"></i> WhatsApp
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="shareViaSMS('${data.event_name}', '${data.attendee_name}', '${data.token}', '${data.event_date}', '${data.ticket_url}')">
                                        <i class="fas fa-sms me-2"></i> SMS
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="copyTicketLink('${data.ticket_url}')">
                                        <i class="fas fa-link me-2"></i> Copy Link
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="${data.export_url}" target="_blank">
                                        <i class="bi bi-file-pdf me-2"></i> Download PDF
                                    </a></li>
                                </ul>`;
                            
                            if (data.is_active) {
                                buttons += `
                                    <form action="${data.deactivate_url}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-warning" title="Deactivate">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>`;
                            }
                            
                            buttons += `</div>`;
                            return buttons;
                        }
                    }
                ],
                order: [[5, 'desc']],
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>',
                paging: false,
                info: false,
                searching: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search tickets by name, email, phone, token, type or event...",
                    lengthMenu: "_MENU_ per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ tickets"
                }
            });

            // Handle filter form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Reset filters
            $('.reset-filters').on('click', function() {
                $('#filter-form')[0].reset();
                table.search('').draw(); // Clear DataTables search
                table.ajax.reload();
            });
        });

        function printTicket(url) {
            const printWindow = window.open(url, '_blank');
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        function shareViaWhatsApp(eventName, attendeeName, token, eventDate, ticketUrl) {
            const text = `Ticket for ${eventName}\n\n` +
                        `Attendee: ${attendeeName}\n` +
                        `Token: ${token}\n` +
                        `Event Date: ${eventDate}\n\n` +
                        `View ticket: ${ticketUrl}`;
            
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(whatsappUrl, '_blank');
        }

        function shareViaSMS(eventName, attendeeName, token, eventDate, ticketUrl) {
            const text = `Ticket for ${eventName}\n\n` +
                        `Attendee: ${attendeeName}\n` +
                        `Token: ${token}\n` +
                        `Event Date: ${eventDate}\n\n` +
                        `View ticket: ${ticketUrl}`;
            
            const smsUrl = `sms:?body=${encodeURIComponent(text)}`;
            window.location.href = smsUrl;
        }

        function copyTicketLink(ticketUrl) {
            navigator.clipboard.writeText(ticketUrl).then(() => {
                alert('Ticket link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy ticket link:', err);
                alert('Failed to copy ticket link. Please try again.');
            });
        }

        // Handle SMS Invitation
        $('.send-invitation-btn').click(function() {
            const ticketId = $(this).data('ticket-id');
            const attendeeName = $(this).data('attendee-name');
            const attendeePhone = $(this).data('attendee-phone');
            const eventName = $(this).data('event-name');
            const attendeeId = $(this).data('attendee-id');
            
            $('#invitationTicketId').val(ticketId);
            $('#invitationAttendeeId').val(attendeeId);
            $('#invitationAttendeeName').text(attendeeName);
            $('#invitationAttendeePhone').text(attendeePhone);
            $('#invitationEventName').text(eventName);
        });

        $('#sendInvitationForm').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            
            submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Sending...');
            
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success('Invitation SMS sent successfully');
                        $('#sendInvitationModal').modal('hide');
                    } else {
                        toastr.error(response.message || 'Failed to send invitation SMS');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to send invitation SMS');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });
    </script>
    @endpush

    <!-- Send Invitation Modal -->
    <div class="modal fade" id="sendInvitationModal" tabindex="-1" aria-labelledby="sendInvitationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendInvitationModalLabel">Send Invitation SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendInvitationForm" action="{{ route('sms.management.send-attendee-invitation', ['event' => $ticket->event->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="ticket_id" id="invitationTicketId">
                    <input type="hidden" name="attendee_id" id="invitationAttendeeId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Attendee</label>
                            <p class="mb-0" id="invitationAttendeeName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <p class="mb-0" id="invitationAttendeePhone"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event</label>
                            <p class="mb-0" id="invitationEventName"></p>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required 
                                      placeholder="Enter your invitation message..."></textarea>
                            <small class="text-muted">You can use variables like {name}, {event}, {date}, {location}, {ticket_number}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 