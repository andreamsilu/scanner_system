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

    <div class="card mb-3">
        <div class="card-body">
            <form id="filter-form" class="row g-3">
                <div class="col-md-3">
                    <label for="event_filter" class="form-label">Event</label>
                    <select class="form-select" id="event_filter" name="event_filter">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status_filter" class="form-label">Status</label>
                    <select class="form-select" id="status_filter" name="status_filter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="type_filter" class="form-label">Ticket Type</label>
                    <select class="form-select" id="type_filter" name="type_filter">
                        <option value="">All Types</option>
                        @foreach($ticketTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, email, phone, token...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary w-100" id="reset-filters">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <div class="text-muted small pagination-info">
                    <!-- Will be updated via AJAX -->
                </div>
                <div class="pagination-container">
                    <!-- Pagination will be handled by Laravel -->
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

        /* Hide DataTables info and pagination */
        .dataTables_info, 
        .dataTables_paginate,
        .dataTables_length,
        .dataTables_filter {
            display: none !important;
        }

        /* Ensure Laravel pagination is visible */
        .pagination {
            display: flex !important;
            margin-bottom: 0;
        }

        /* Loading indicator */
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            padding: 1rem !important;
            color: #666 !important;
            font-weight: 500 !important;
        }

        /* Filter form styles */
        .form-label {
    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable with server-side processing
            var table = $('#tickets-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: window.location.href,
                    type: 'GET',
                    data: function(d) {
                        d.page = (d.start / d.length) + 1;
                        d.search = d.search.value;
                        d.order = d.order[0] ? {
                            column: d.order[0].column,
                            dir: d.order[0].dir
                        } : null;
                    },
                    dataSrc: function(json) {
                        // Update pagination info
                        $('.pagination-info').html(
                            `Showing ${json.pagination.first_item} to ${json.pagination.last_item} of ${json.pagination.total} entries`
                        );

                        // Update pagination links
                        let paginationHtml = '';
                        if (json.pagination.last_page > 1) {
                            paginationHtml = '<ul class="pagination">';
                            
                            // Previous page
                            paginationHtml += `<li class="page-item ${json.pagination.current_page === 1 ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${json.pagination.current_page - 1}">Previous</a>
                            </li>`;

                            // Page numbers
                            for (let i = 1; i <= json.pagination.last_page; i++) {
                                paginationHtml += `<li class="page-item ${json.pagination.current_page === i ? 'active' : ''}">
                                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                                </li>`;
                            }

                            // Next page
                            paginationHtml += `<li class="page-item ${json.pagination.current_page === json.pagination.last_page ? 'disabled' : ''}">
                                <a class="page-link" href="#" data-page="${json.pagination.current_page + 1}">Next</a>
                            </li>`;

                            paginationHtml += '</ul>';
                        }
                        $('.pagination-container').html(paginationHtml);

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
                                            title="Send Invitation SMS"
                                            data-bs-toggle="modal"
                                            data-bs-target="#sendInvitationModal"
                                            data-ticket-id="${data.ticket_id}"
                                            data-attendee-name="${data.attendee_name}"
                                            data-attendee-phone="${data.attendee_phone}"
                                            data-event-name="${data.event_name}">
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
                dom: '<"row mb-2"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>',
                paging: false,
                info: false,
                searching: true,
                lengthMenu: [[15, 25, 50, -1], [15, 25, 50, "All"]],
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
                                    format: {
                                        body: function(data, row, column, node) {
                                            if (column === 0) {
                                                return $(data).find('.fw-medium').text();
                                            }
                                            return data;
                                        }
                                    }
                                },
                                title: 'Tickets List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5],
                                    format: {
                                        body: function(data, row, column, node) {
                                            if (column === 0) {
                                                return $(data).find('.fw-medium').text();
                                            }
                                            return data;
                                        }
                                    }
                                },
                                title: 'Tickets List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(doc) {
                                    doc.defaultStyle.fontSize = 9;
                                    doc.styles.tableHeader.fontSize = 10;
                                    doc.styles.tableHeader.fillColor = '#f8f9fa';
                                    doc.styles.tableHeader.color = '#212529';
                                    doc.pageMargins = [20, 40, 20, 40];
                                    doc.defaultStyle.alignment = 'left';
                                    doc.styles.tableHeader.alignment = 'left';
                                }
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer me-2"></i>Print',
                                className: 'btn btn-sm btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5],
                                    format: {
                                        body: function(data, row, column, node) {
                                            if (column === 0) {
                                                return $(data).find('.fw-medium').text();
                                            }
                                            return data;
                                        }
                                    }
                                },
                                title: 'Tickets List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            }
                        ]
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading tickets...',
                    search: "_INPUT_",
                    searchPlaceholder: "Search tickets...",
                    emptyTable: "No tickets found.",
                    zeroRecords: "No matching tickets found."
                },
                drawCallback: function() {
                    // Reinitialize tooltips and other plugins
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Handle pagination clicks
            $(document).on('click', '.pagination .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled')) {
                    table.page(page - 1).draw('page');
                }
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
                
                $('#invitationTicketId').val(ticketId);
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