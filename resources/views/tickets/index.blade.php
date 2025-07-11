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
                            <a class="dropdown-item" href="{{ route('events.tickets.export', ['event' => $event, 'format' => 'excel']) }}">
                                <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('events.tickets.export', ['event' => $event, 'format' => 'pdf']) }}">
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

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-search me-2"></i>Search & Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by attendee name, token, email, phone...">
                </div>
                <div class="col-md-2">
                    <label for="ticketTypeFilter" class="form-label">Ticket Type</label>
                    <select class="form-select" id="ticketTypeFilter">
                        <option value="">All Types</option>
                        <option value="regular">Regular</option>
                        <option value="vip">VIP</option>
                        <option value="single">Single</option>
                        <option value="double">Double</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="used">Used</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateRangeFilter" class="form-label">Date Range</label>
                    <input type="text" class="form-control" id="dateRangeFilter" placeholder="Select date range">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <!-- Loading overlay -->
            <div id="loadingOverlay" class="position-absolute w-100 h-100 d-none" style="background: rgba(255,255,255,0.8); z-index: 1000; top: 0; left: 0;">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
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
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
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
        
        /* SMS Feedback Styles */
        .table-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            transition: background-color 0.3s ease;
        }
        
        .table-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            transition: background-color 0.3s ease;
        }
        
        .badge.bg-success {
            animation: fadeIn 0.5s ease-in;
        }
        
        .badge.bg-danger {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Button state transitions */
        .send-invitation-btn {
            transition: all 0.3s ease;
        }
        
        .send-invitation-btn.btn-success {
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        .send-invitation-btn.btn-danger {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
    @endpush

    @push('scripts')
    <!-- Date Range Picker JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // Global error handler to ensure loading overlay is hidden on any error
            $(document).ajaxError(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('send-attendee-invitation')) {
                    $('#smsLoadingOverlay').addClass('d-none');
                    const submitBtn = $('#sendInvitationBtn');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
                    console.error('Global AJAX error handler - reset loading state');
                }
            });
            // Initialize DataTable with server-side processing
            var table = $('#tickets-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 15,
                order: [[5, 'desc']],
                ajax: {
                    url: '{{ route("events.tickets.data", $event) }}',
                    type: 'GET',
                    data: function(d) {
                        // Add custom filters to the request
                        d.ticket_type = $('#ticketTypeFilter').val();
                        d.status = $('#statusFilter').val();
                        d.date_range = $('#dateRangeFilter').val();
                    },
                    beforeSend: function() {
                        $('#loadingOverlay').removeClass('d-none');
                    },
                    complete: function() {
                        $('#loadingOverlay').addClass('d-none');
                    }
                },
                columns: [
                    {
                        data: 'attendee',
                        name: 'attendee',
                        render: function(data, type, row) {
                            if (data) {
                                return '<div class="d-flex flex-column">' +
                                       '<span class="fw-medium">' + data.name + '</span>' +
                                       '<small class="text-muted">#' + data.token + '</small>' +
                                       '</div>';
                            }
                            return '<span class="text-muted">Unassigned</span>';
                        }
                    },
                    {
                        data: 'event_name',
                        name: 'event_name',
                        render: function(data, type, row) {
                            return '<a href="{{ route("events.show", $event) }}" class="text-decoration-none">' +
                                   data.substring(0, 20) + (data.length > 20 ? '...' : '') +
                                   '</a>';
                        }
                    },
                    {
                        data: 'type',
                        name: 'type',
                        render: function(data, type, row) {
                            var badgeClass = 'bg-secondary';
                            switch(data) {
                                case 'vip': badgeClass = 'bg-warning'; break;
                                case 'regular': badgeClass = 'bg-secondary'; break;
                                case 'single': badgeClass = 'bg-primary'; break;
                                case 'double': badgeClass = 'bg-success'; break;
                            }
                            return '<span class="badge ' + badgeClass + ' px-2 py-1">' + 
                                   data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            return '<span class="badge bg-' + data.badge + ' px-2 py-1">' + 
                                   data.value.charAt(0).toUpperCase() + data.value.slice(1) + '</span>';
                        }
                    },
                    {
                        data: 'last_scan',
                        name: 'last_scan',
                        render: function(data, type, row) {
                            if (data) {
                                return '<span class="text-muted" title="' + data.time + '">' + data.diff + '</span>';
                            }
                            return '<span class="text-muted">Never</span>';
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            return '<span class="text-muted">' + new Date(data).toLocaleDateString('en-GB') + '</span>';
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var actions = '<div class="btn-group btn-group-sm">';
                            
                            // View button
                            actions += '<a href="' + data.view_url + '" class="btn btn-outline-info" title="View Details">' +
                                      '<i class="bi bi-eye"></i></a>';
                            
                            // Print button
                            actions += '<button type="button" class="btn btn-outline-primary" title="Print Ticket" ' +
                                      'onclick="printTicket(\'' + data.print_url + '\')">' +
                                      '<i class="bi bi-printer"></i></button>';
                            
                            // SMS button (if attendee has phone)
                            if (row.attendee && row.attendee.phone) {
                                actions += '<button type="button" class="btn btn-outline-success send-invitation-btn" ' +
                                          'title="Send Invitation SMS" data-ticket-id="' + row.id + '" ' +
                                          'data-attendee-name="' + row.attendee.name + '" ' +
                                          'data-attendee-phone="' + row.attendee.phone + '" ' +
                                          'data-attendee-id="' + row.attendee.id + '" ' +
                                          'data-ticket-type="' + row.type + '" ' +
                                          'data-token="' + row.attendee.token + '" ' +
                                          'onclick="prepareInvitation(this)">' +
                                          '<i class="bi bi-envelope"></i></button>';
                            } else {
                                actions += '<button type="button" class="btn btn-outline-secondary" ' +
                                          'title="No phone number available" disabled>' +
                                          '<i class="bi bi-envelope"></i></button>';
                            }
                            
                            // Share dropdown
                            actions += '<button type="button" class="btn btn-outline-success" title="Share Ticket" ' +
                                      'data-bs-toggle="dropdown" aria-expanded="false">' +
                                      '<i class="bi bi-share"></i></button>';
                            actions += '<ul class="dropdown-menu dropdown-menu-end">';
                            actions += '<li><a class="dropdown-item" href="#" onclick="shareViaWhatsApp(\'' + 
                                      row.event_name + '\', \'' + (row.attendee ? row.attendee.name : 'Unassigned') + 
                                      '\', \'' + row.attendee.token + '\', \'' + row.event_date + '\', \'' + 
                                      '{{ url("/tickets") }}/' + row.attendee.token + '/verify\')">' +
                                      '<i class="fab fa-whatsapp me-2"></i> WhatsApp</a></li>';
                            actions += '<li><a class="dropdown-item" href="#" onclick="shareViaSMS(\'' + 
                                      row.event_name + '\', \'' + (row.attendee ? row.attendee.name : 'Unassigned') + 
                                      '\', \'' + row.attendee.token + '\', \'' + row.event_date + '\', \'' + 
                                      '{{ url("/tickets") }}/' + row.attendee.token + '/verify\')">' +
                                      '<i class="fas fa-sms me-2"></i> SMS</a></li>';
                            actions += '<li><a class="dropdown-item" href="#" onclick="copyTicketLink(\'' + 
                                      '{{ url("/tickets") }}/' + row.attendee.token + '/verify\')">' +
                                      '<i class="fas fa-link me-2"></i> Copy Link</a></li>';
                            actions += '<li><hr class="dropdown-divider"></li>';
                            actions += '<li><a class="dropdown-item" href="' + data.export_url + '" target="_blank">' +
                                      '<i class="bi bi-file-pdf me-2"></i> Download PDF</a></li>';
                            actions += '</ul>';
                            
                            // Deactivate button
                            if (data.deactivate_url) {
                                actions += '<form action="' + data.deactivate_url + '" method="POST" class="d-inline" ' +
                                          'onsubmit="return confirm(\'Are you sure you want to deactivate this ticket?\');">' +
                                          '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                          '<input type="hidden" name="_method" value="PATCH">' +
                                          '<button type="submit" class="btn btn-outline-warning" title="Deactivate">' +
                                          '<i class="bi bi-x-circle"></i></button></form>';
                            }
                            
                            // Download JPG button
                            actions += '<a href="' + data.download_jpg_url + '" class="btn btn-outline-warning" title="Download JPG">' +
                                      '<i class="bi bi-file-image"></i></a>';
                            
                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
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

            // Initialize date range picker
            $('#dateRangeFilter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#dateRangeFilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
                table.ajax.reload();
            });

            $('#dateRangeFilter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                table.ajax.reload();
            });

            // Event handlers for filters
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#ticketTypeFilter, #statusFilter').on('change', function() {
                table.ajax.reload();
            });

            // Clear filters button
            $('#clearFilters').on('click', function() {
                $('#searchInput').val('');
                $('#ticketTypeFilter').val('');
                $('#statusFilter').val('');
                $('#dateRangeFilter').val('');
                table.search('').draw();
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

        // Enhanced SMS Invitation Handler
        function prepareInvitation(button) {
            const ticketId = $(button).data('ticket-id');
            const attendeeName = $(button).data('attendee-name');
            const attendeePhone = $(button).data('attendee-phone');
            const attendeeId = $(button).data('attendee-id');
            const ticketType = $(button).data('ticket-type');
            const token = $(button).data('token');
            
            // Validate required data
            if (!attendeeName || !attendeePhone) {
                toastr.error('Missing required attendee information for SMS invitation.');
                return false;
            }
            
            // Show loading state
            $(button).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            // Prepare invitation data
            sendInvitation(ticketId, attendeeId, attendeeName, attendeePhone, ticketType, token);
            
            return false; // Prevent default modal behavior
        }

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
            const hours = eventDate.getHours();
            const minutes = eventDate.getMinutes().toString().padStart(2, '0');
            const period = hours >= 12 ? 'jioni' : 'asubuhi';
            const formattedTime = `${hours}:${minutes}`;

            // Create predefined SMS message with actual values from the modal
            const smsTemplate = `Ndugu ${attendeeName}, unakaribishwa kwenye {{ $event->name }} itakayofanyika ${dayName} ${eventDate.toLocaleDateString('en-GB')} saa ${formattedTime} ${period}. Mahali: {{ $event->location }}. Ukumbi: {{ $event->venue }}. Tiketi: ${token} (${ticketType.toUpperCase()}). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824`;
            
            // Validate that we have all required data
            if (!attendeeName || !token || !ticketType) {
                console.error('Missing required data for SMS template:', {
                    attendeeName,
                    token,
                    ticketType,
                    eventName: '{{ $event->name }}',
                    eventDate: eventDate.toLocaleDateString('en-GB'),
                    eventTime: `${formattedTime} ${period}`
                });
            }
            
            // Set the predefined message in the hidden input
            $('#predefinedMessage').val(smsTemplate);
            
            // Display the template in the alert box
            $('#sampleSmsTemplate').text(smsTemplate);

            // Hide the custom message textarea since we're using predefined template
            $('#customMessageContainer').hide();
            
            $('#sendInvitationModal').modal('show');
        }

        // Enhanced form submission handler
        $('#sendInvitationForm').on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $('#sendInvitationBtn');
            const originalBtnText = submitBtn.html();
            
            // Show loading state
            submitBtn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...');
            
            // Show loading overlay
            $('#smsLoadingOverlay').removeClass('d-none');
            
            // Set a timeout to hide loading overlay after 30 seconds (safety mechanism)
            const loadingTimeout = setTimeout(function() {
                $('#smsLoadingOverlay').addClass('d-none');
                const submitBtn = $('#sendInvitationBtn');
                submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
                console.warn('Loading overlay hidden by timeout - SMS request may have timed out');
            }, 30000);

            // Get the predefined message
            const message = $('#predefinedMessage').val();
            
            // Log the message being sent for debugging
            console.log('Sending SMS message:', {
                message: message,
                messageLength: message.length,
                attendeeName: $('#invitationAttendeeName').text(),
                phone: $('#invitationAttendeePhone').text()
            });

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: {
                    ...$(this).serializeArray().reduce((obj, item) => ({ ...obj, [item.name]: item.value }), {}),
                    message: message // Send the predefined message
                },
                success: function(response) {
                    // Hide loading overlay first
                    $('#smsLoadingOverlay').addClass('d-none');
                    
                    // Reset the submit button immediately
                    const submitBtn = $('#sendInvitationBtn');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
                    
                    // Hide the modal
                    $('#sendInvitationModal').modal('hide');
                    
                    // Show success message with details
                    if (response.success) {
                        // Enhanced success feedback
                        toastr.success(`✅ SMS invitation sent successfully to ${response.recipient}!`, 'Success', {
                            timeOut: 8000,
                            extendedTimeOut: 3000,
                            closeButton: true,
                            progressBar: true
                        });
                        
                        // Show detailed success message
                        const successDetails = `
                            <div class="mt-2">
                                <small class="text-muted">
                                    📱 Phone: ${response.formatted_phone || response.original_phone}<br>
                                    🆔 Message ID: ${response.sms_result?.messageId || 'N/A'}<br>
                                    📋 Reference: ${response.reference || 'N/A'}
                                </small>
                            </div>
                        `;
                        
                        // Show additional success notification with details
                        setTimeout(() => {
                            toastr.info(`📊 SMS Details: ${successDetails}`, 'Message Details', {
                                timeOut: 10000,
                                extendedTimeOut: 5000
                            });
                        }, 1000);
                        
                        // Show summary notification
                        setTimeout(() => {
                            toastr.success(`🎉 SMS invitation sent successfully! The attendee should receive the message shortly.`, 'SMS Summary', {
                                timeOut: 6000,
                                extendedTimeOut: 3000
                            });
                        }, 2000);
                        
                        // Show additional details in console for debugging
                        console.log('SMS sent successfully:', {
                            recipient: response.recipient,
                            phone: response.formatted_phone || response.original_phone,
                            message_id: response.sms_result?.messageId,
                            reference: response.reference
                        });
                    } else {
                        toastr.warning('⚠️ SMS was processed but may not have been delivered successfully.', 'Warning', {
                            timeOut: 10000,
                            extendedTimeOut: 5000
                        });
                    }
                    
                    // Update the specific button that was clicked to show success state
                    const clickedButton = $('.send-invitation-btn').filter(function() {
                        return $(this).data('attendee-id') === $('#invitationAttendeeId').val();
                    });
                    
                    if (clickedButton.length) {
                        // Change button to success state
                        clickedButton
                            .removeClass('btn-outline-success')
                            .addClass('btn-success')
                            .prop('disabled', true)
                            .html('<i class="bi bi-check-circle"></i><span class="ms-1 d-none">Sent</span>')
                            .attr('title', 'SMS sent successfully');
                        
                        // Add visual feedback to the table row
                        const tableRow = clickedButton.closest('tr');
                        tableRow.addClass('table-success');
                        
                        // Add a small success indicator
                        tableRow.find('td:first').append('<span class="badge bg-success ms-2">SMS Sent</span>');
                        
                        // Reset button after 5 seconds
                        setTimeout(() => {
                            clickedButton
                                .removeClass('btn-success')
                                .addClass('btn-outline-success')
                                .prop('disabled', false)
                                .html('<i class="bi bi-envelope"></i><span class="ms-1 d-none">SMS</span>')
                                .attr('title', 'Send Invitation SMS');
                            
                            // Remove row highlighting
                            tableRow.removeClass('table-success');
                            tableRow.find('.badge.bg-success').remove();
                        }, 5000);
                    } else {
                        // Fallback: reset all send buttons to normal state
                        $('.send-invitation-btn').prop('disabled', false).html('<i class="bi bi-envelope"></i><span class="ms-1 d-none">SMS</span>');
                    }
                },
                error: function(xhr) {
                    // Hide loading overlay first
                    $('#smsLoadingOverlay').addClass('d-none');
                    
                    // Reset the submit button immediately
                    const submitBtn = $('#sendInvitationBtn');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
                    
                    let errorMessage = 'Failed to send SMS invitation';
                    let errorTitle = 'Error';
                    let errorIcon = '❌';
                    
                    if (xhr.responseJSON) {
                        errorMessage = xhr.responseJSON.message || errorMessage;
                        
                        // Handle specific error cases
                        if (xhr.status === 422) {
                            errorTitle = 'Validation Error';
                            errorIcon = '⚠️';
                        } else if (xhr.status === 429) {
                            errorTitle = 'Rate Limit Exceeded';
                            errorMessage = 'Too many SMS requests. Please wait a moment before trying again.';
                            errorIcon = '⏰';
                        } else if (xhr.status === 500) {
                            errorTitle = 'Server Error';
                            errorIcon = '🔧';
                        }
                    }
                    
                    // Enhanced error feedback
                    toastr.error(`${errorIcon} ${errorMessage}`, errorTitle, {
                        timeOut: 12000,
                        extendedTimeOut: 5000,
                        closeButton: true,
                        progressBar: true
                    });
                    
                    // Show error details
                    const errorDetails = `
                        <div class="mt-2">
                            <small class="text-muted">
                                📱 Phone: ${$('#invitationAttendeePhone').text()}<br>
                                👤 Attendee: ${$('#invitationAttendeeName').text()}<br>
                                🔢 Status Code: ${xhr.status}<br>
                                ⏰ Time: ${new Date().toLocaleTimeString()}
                            </small>
                        </div>
                    `;
                    
                    // Show additional error notification with details
                    setTimeout(() => {
                        toastr.warning(`📊 Error Details: ${errorDetails}`, 'Error Information', {
                            timeOut: 15000,
                            extendedTimeOut: 8000
                        });
                    }, 1000);
                    
                    // Update the specific button that was clicked to show error state
                    const clickedButton = $('.send-invitation-btn').filter(function() {
                        return $(this).data('attendee-id') === $('#invitationAttendeeId').val();
                    });
                    
                    if (clickedButton.length) {
                        // Change button to error state temporarily
                        clickedButton
                            .removeClass('btn-outline-success')
                            .addClass('btn-danger')
                            .prop('disabled', false)
                            .html('<i class="bi bi-exclamation-triangle"></i><span class="ms-1 d-none">Failed</span>')
                            .attr('title', 'SMS sending failed - click to retry');
                        
                        // Add visual feedback to the table row
                        const tableRow = clickedButton.closest('tr');
                        tableRow.addClass('table-danger');
                        
                        // Add a small error indicator
                        tableRow.find('td:first').append('<span class="badge bg-danger ms-2">SMS Failed</span>');
                        
                        // Reset button after 3 seconds
                        setTimeout(() => {
                            clickedButton
                                .removeClass('btn-danger')
                                .addClass('btn-outline-success')
                                .prop('disabled', false)
                                .html('<i class="bi bi-envelope"></i><span class="ms-1 d-none">SMS</span>')
                                .attr('title', 'Send Invitation SMS');
                            
                            // Remove row highlighting
                            tableRow.removeClass('table-danger');
                            tableRow.find('.badge.bg-danger').remove();
                        }, 3000);
                    } else {
                        // Fallback: reset all send buttons to normal state
                        $('.send-invitation-btn').prop('disabled', false).html('<i class="bi bi-envelope"></i><span class="ms-1 d-none">SMS</span>');
                    }
                    
                    // Log error for debugging
                    console.error('SMS sending failed:', {
                        status: xhr.status,
                        response: xhr.responseJSON,
                        error: errorMessage
                    });
                },
                complete: function() {
                    // Clear the loading timeout
                    clearTimeout(loadingTimeout);
                    
                    // Hide loading overlay
                    $('#smsLoadingOverlay').addClass('d-none');
                    
                    // Reset button (fallback)
                    const submitBtn = $('#sendInvitationBtn');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
                    
                    // Debug logging
                    console.log('AJAX complete - loading overlay hidden, submit button reset');
                }
            });
        });
        
        // Reset button states when modal is closed
        $('#sendInvitationModal').on('hidden.bs.modal', function () {
            // Hide loading overlay if it's still visible
            $('#smsLoadingOverlay').addClass('d-none');
            
            // Reset the submit button
            const submitBtn = $('#sendInvitationBtn');
            submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send SMS');
            
            // Reset all send buttons to normal state
            $('.send-invitation-btn').prop('disabled', false).html('<i class="bi bi-envelope"></i><span class="ms-1 d-none">SMS</span>');
            
            // Debug logging
            console.log('Modal closed - loading overlay hidden, buttons reset');
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
                    
                    <!-- Loading overlay -->
                    <div id="smsLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none" style="z-index: 1050;">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mb-0">Sending SMS...</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 