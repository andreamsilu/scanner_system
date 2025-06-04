<x-app-layout>
    <x-page-header 
        title="Tickets"
        :breadcrumbs="[
            'Events' => route('events.index'),
            'Tickets' => null
        ]"
    >
        <x-slot name="actions">
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-2"></i>Export All
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('tickets.export', ['format' => 'excel']) }}">
                                <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('tickets.export', ['format' => 'pdf']) }}">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('events.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Events
                </a>
            </div>
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
                                                    data-ticket-type="{{ $ticket->type }}"
                                                    data-token="{{ $ticket->token }}">
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
                                                <a class="dropdown-item" href="#" onclick="shareViaWhatsApp('{{ $ticket->event->name }}', '{{ $ticket->attendee->name }}', '{{ $ticket->token }}', '{{ $ticket->event->start_date->format('M d, Y') }}', '{{ route('tickets.verify', ['token' => $ticket->token]) }}')">
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

            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <div class="text-muted small">
                    Showing {{ $tickets->firstItem() ?? 0 }} to {{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} entries
                </div>
                <div>
                    {{ $tickets->links() }}
                </div>
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
                pageLength: 15,
                order: [[5, 'desc']],
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
                                    columns: [0, 1, 2, 3, 4, 5],
                                    search: 'none',
                                    format: {
                                        body: function(data, row, column, node) {
                                            if (column === 0) {
                                                return $(data).find('.fw-medium').text();
                                            }
                                            if (column === 2 || column === 3) {
                                                return $(data).text().trim();
                                            }
                                            return data;
                                        }
                                    }
                                },
                                title: 'Tickets List - {{ now()->format('Y-m-d H:i:s') }}',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(xlsx) {
                                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                                    $('row:first c', sheet).attr('s', '2'); // Bold headers
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5],
                                    search: 'none',
                                    format: {
                                        body: function(data, row, column, node) {
                                            if (column === 0) {
                                                return $(data).find('.fw-medium').text();
                                            }
                                            if (column === 2 || column === 3) {
                                                return $(data).text().trim();
                                            }
                                            return data;
                                        }
                                    }
                                },
                                title: 'Tickets List - {{ now()->format('Y-m-d H:i:s') }}',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(doc) {
                                    doc.defaultStyle.fontSize = 9;
                                    doc.styles.tableHeader.fontSize = 10;
                                    doc.styles.tableHeader.fillColor = '#f8f9fa';
                                    doc.styles.tableHeader.color = '#212529';
                                    doc.pageMargins = [20, 40, 20, 40];
                                    doc.defaultStyle.alignment = 'left';
                                    doc.styles.tableHeader.alignment = 'left';
                                    
                                    // Add footer with page numbers
                                    doc.footer = function(currentPage, pageCount) {
                                        return {
                                            columns: [
                                                {
                                                    text: 'Page ' + currentPage.toString() + ' of ' + pageCount,
                                                    alignment: 'center',
                                                    fontSize: 8,
                                                    margin: [0, 10]
                                                }
                                            ]
                                        };
                                    };
                                }
                            }
                        ]
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search tickets...",
                    lengthMenu: "_MENU_ per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ tickets"
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
            const attendeeId = $(this).data('attendee-id');
            const ticketType = $(this).data('ticket-type');
            const token = $(this).data('token');
            
            // Validate required data
            if (!attendeeName || !attendeePhone) {
                toastr.error('Missing required attendee information.');
                return;
            }
            
            sendInvitation(ticketId, attendeeId, attendeeName, attendeePhone, ticketType, token);
        });

        // Update the sendInvitation function to use predefined template
        function sendInvitation(ticketId, attendeeId, attendeeName, phone, ticketType, token) {
            // Set form values
            $('#invitationTicketId').val(ticketId);
            $('#invitationAttendeeId').val(attendeeId);
            $('#invitationAttendeeName').text(attendeeName);
            $('#invitationAttendeePhone').text(phone);
            $('#invitationTicketType').text(ticketType ? ticketType.toUpperCase() : 'REGULAR');
            $('#invitationTicketNumber').text(token || 'N/A');

            // Format date to include day name in Swahili
            const eventDate = new Date('{{ $event->start_date }}');
            const dayNames = {
                'Monday': 'Jumatatu',
                'Tuesday': 'Jumanne',
                'Wednesday': 'Jumatano',
                'Thursday': 'Alhamisi',
                'Friday': 'Ijumaa',
                'Saturday': 'Jumamosi',
                'Sunday': 'Jumapili'
            };
            const dayName = dayNames[eventDate.toLocaleDateString('en-US', { weekday: 'long' })];

            // Format time with AM/PM in Swahili
            const time = '{{ $event->start_time }}';
            const [hours, minutes] = time.split(':');
            const period = parseInt(hours) >= 12 ? 'jioni' : 'asubuhi';
            const formattedTime = `${hours}:${minutes}`;

            // Create predefined SMS message with actual values
            const smsTemplate = `Ndugu ${attendeeName}, unakaribishwa kwenye {{ $event->name }} itakayofanyika ${dayName} ${eventDate.toLocaleDateString('en-GB')} saa ${formattedTime} ${period} katika {{ $event->location }}. Tiketi: ${token} (${ticketType.toUpperCase()}). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824`;
            
            // Set the predefined message in the hidden input
            $('#predefinedMessage').val(smsTemplate);
            
            // Display the template in the alert box
            $('#sampleSmsTemplate').text(smsTemplate);

            // Hide the custom message textarea since we're using predefined template
            $('#customMessageContainer').hide();
            
            $('#sendInvitationModal').modal('show');
        }

        // Handle form submission
        $('#sendInvitationForm').on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $('#sendInvitationBtn');
            const originalBtnText = submitBtn.html();
            
            submitBtn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...');

            // Get the predefined message
            const message = $('#predefinedMessage').val();

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: {
                    ...$(this).serializeArray().reduce((obj, item) => ({ ...obj, [item.name]: item.value }), {}),
                    message: message // Send the predefined message
                },
                success: function(response) {
                    $('#sendInvitationModal').modal('hide');
                    toastr.success('SMS invitation sent successfully!');
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to send SMS invitation');
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendInvitationModalLabel">Send Invitation SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendInvitationForm" action="{{ route('sms.management.send-attendee-invitation', ['event' => $event->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="ticket_id" id="invitationTicketId">
                    <input type="hidden" name="attendee_id" id="invitationAttendeeId">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Attendee</label>
                                    <p class="mb-0" id="invitationAttendeeName"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Phone Number</label>
                                    <p class="mb-0" id="invitationAttendeePhone"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Ticket Type</label>
                                    <p class="mb-0" id="invitationTicketType"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Ticket Number</label>
                                    <p class="mb-0" id="invitationTicketNumber"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Event Details</label>
                                    <p class="mb-0">
                                        <strong>Name:</strong> {{ $event->name }}<br>
                                        <strong>Date:</strong> {{ $event->start_date->format('d/m/Y') }}<br>
                                        <strong>Time:</strong> {{ $event->start_time }}<br>
                                        <strong>Venue:</strong> {{ $event->location }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">SMS Message</label>
                            <div class="alert alert-info">
                                <p class="mb-0" id="sampleSmsTemplate"></p>
                            </div>
                            <input type="hidden" id="predefinedMessage" name="message">
                        </div>

                        <div id="customMessageContainer" class="mb-3" style="display: none;">
                            <label for="customMessage" class="form-label fw-medium">Custom Message (Optional)</label>
                            <textarea class="form-control" id="customMessage" name="custom_message" rows="3" 
                                placeholder="Enter a custom message or leave blank to use the template above"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="sendInvitationBtn">
                            <i class="fas fa-paper-plane me-1"></i> Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 