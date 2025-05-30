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

    <style>
        .ticket-invite {
            max-width: 600px;
            margin: 40px auto;
            background: #f9f6f2;
            border-radius: 12px;
            border: 2px solid #e0c68a;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            padding: 2.5rem 2rem 2rem 2rem;
            position: relative;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        .ticket-invite .border-deco {
            border: 2px solid #e0c68a;
            border-radius: 10px;
            padding: 2rem 1.5rem;
            position: relative;
        }
        .ticket-invite .border-deco:before,
        .ticket-invite .border-deco:after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            height: 2px;
            background: repeating-linear-gradient(90deg, #e0c68a, #e0c68a 8px, transparent 8px, transparent 16px);
        }
        .ticket-invite .border-deco:before { top: 0; }
        .ticket-invite .border-deco:after { bottom: 0; }
        .ticket-invite h2 {
            font-size: 2.2rem;
            letter-spacing: 0.12em;
            color: #a68a3f;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .ticket-invite h3 {
            font-size: 1.2rem;
            color: #a68a3f;
            margin-bottom: 2rem;
            font-weight: 400;
            font-style: italic;
        }
        .ticket-invite .section-title {
            font-size: 1.1rem;
            color: #bfa14a;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.08em;
            font-weight: 600;
        }
        .ticket-invite .info {
            font-size: 1.05rem;
            color: #5a4a1a;
            margin-bottom: 0.5rem;
        }
        .ticket-invite .details {
            font-size: 1rem;
            color: #7a6a3a;
            margin-bottom: 0.5rem;
        }
        .ticket-invite .gold {
            color: #bfa14a;
        }
        .ticket-invite .qr {
            margin: 2rem 0 1rem 0;
            text-align: center;
        }
        .ticket-invite .actions {
            margin-top: 1.5rem;
            text-align: center;
        }
        @media print {
            body { background: #fff !important; }
            .ticket-invite { box-shadow: none !important; margin: 0 auto; }
            .actions { display: none !important; }
        }
    </style>

    <div class="container">
        @forelse($tickets as $ticket)
            <div class="ticket-invite">
                <div class="border-deco">
                    <div class="text-center mb-4">
                        <div class="gold" style="letter-spacing:0.15em; font-size:0.95rem;">TICKET FOR</div>
                        <h2>{{ strtoupper($ticket->event->name) }}</h2>
                        <h3>Admit One</h3>
                    </div>
                    <div class="section-title text-center">Attendee</div>
                    <div class="info text-center" style="font-size:1.3rem; font-weight:600; letter-spacing:0.08em;">
                        {{ $ticket->attendee ? strtoupper($ticket->attendee->name) : 'UNASSIGNED' }}
                    </div>
                    <div class="details text-center">
                        Ticket Type: <span class="gold">{{ ucfirst($ticket->type) }}</span><br>
                        Ticket ID: <span class="gold">{{ $ticket->token }}</span>
                    </div>
                    <div class="section-title text-center">Event Details</div>
                    <div class="details text-center">
                        <span class="gold">Date:</span> {{ $ticket->event->start_date->format('l, F j, Y') }}<br>
                        <span class="gold">Time:</span> {{ $ticket->event->start_date->format('g:i A') }}<br>
                        <span class="gold">Venue:</span> {{ $ticket->event->venue }}<br>
                        <span class="gold">Location:</span> {{ $ticket->event->location }}
                    </div>
                    <div class="qr">
                        @if(isset($ticket->qr_code_url))
                            <img src="{{ $ticket->qr_code_url }}" alt="QR Code" style="width:120px; height:120px;" />
                        @else
                            <div class="gold" style="font-size:0.95rem;">QR Code Unavailable</div>
                        @endif
                    </div>
                    <div class="details text-center" style="font-size:0.95rem;">
                        Issued: {{ $ticket->created_at->format('M d, Y') }}<br>
                        Status: <span class="gold">{{ $ticket->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="actions">
                        <a href="{{ route('events.events.tickets.print', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" class="btn btn-outline-primary me-2" target="_blank">
                            <i class="bi bi-printer"></i> Print
                        </a>
                        <a href="{{ route('events.events.tickets.export', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" class="btn btn-outline-danger me-2" target="_blank">
                            <i class="bi bi-file-pdf"></i> PDF
                        </a>
                        <button type="button" class="btn btn-outline-success" onclick="copyTicketLink('{{ route('tickets.verify', $ticket->token) }}')">
                            <i class="bi bi-link-45deg"></i> Copy Link
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center mt-5">No tickets found.</div>
        @endforelse
    </div>

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
    </script>
    @endpush
</x-app-layout> 