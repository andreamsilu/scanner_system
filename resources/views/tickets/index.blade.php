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

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="tickets-table">
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
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            $('#tickets-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("tickets.data") }}',
                    type: 'GET'
                },
                columns: [
                    { 
                        data: 'attendee',
                        name: 'attendee',
                        render: function(data, type, row) {
                            if (data) {
                                return `<div class="d-flex flex-column">
                                    <span class="fw-medium">${data.name}</span>
                                    <small class="text-muted">#${row.token}</small>
                                </div>`;
                            }
                            return '<span class="text-muted">Unassigned</span>';
                        }
                    },
                    { 
                        data: 'event',
                        name: 'event',
                        render: function(data) {
                            return `<a href="/events/${data.id}" class="text-decoration-none">${data.name}</a>`;
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
                                        <form action="/tickets/${row.id}/deactivate" 
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
                order: [[5, 'desc']],
                pageLength: 20,
                dom: '<"row mb-2"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-2"></i>Export',
                        className: 'btn btn-sm btn-primary',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                                className: 'btn btn-sm btn-success',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
                                }
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer me-2"></i>Print',
                                className: 'btn btn-sm btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
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