<x-app-layout>
    <x-slot:title>
        Ticket Details
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Ticket Details</h5>
                        <div class="mt-2">
                            <h4 class="text-primary mb-0">{{ $ticket->attendee->name }}</h4>
                            @if($ticket->attendee->email)
                                <small class="text-muted">{{ $ticket->attendee->email }}</small>
                            @endif
                            @if($ticket->attendee->phone)
                                <small class="text-muted d-block">{{ $ticket->attendee->phone }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="printTicket()">
                            <i class="fas fa-print me-1"></i> Print Ticket
                        </button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Share Ticket</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" onclick="shareViaWhatsApp()">
                                    <i class="fab fa-whatsapp me-2"></i> Share via WhatsApp
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="shareViaSMS()">
                                    <i class="fas fa-sms me-2"></i> Share via SMS
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="copyTicketLink()">
                                    <i class="fas fa-link me-2"></i> Copy Ticket Link
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Print-only ticket design (hidden by default) -->
                    <div id="printableTicket" class="d-none">
                        <div class="text-center mb-4">
                            @if($event->logo_path)
                                <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="event-logo mb-3" style="max-width: 150px;">
                            @endif
                            <h4 class="mb-2 text-primary">{{ $event->name }}</h4>
                            <p class="mb-0 text-muted">{{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}</p>
                        </div>

                        <div class="invitation-message text-center mb-4 p-4 border rounded bg-light">
                            <h5 class="mb-3 text-primary">Karibu! (Welcome!)</h5>
                            <p class="mb-2">Mpendwa {{ $ticket->attendee->name }},</p>
                            <p class="mb-2">Tunafurahi kukualika kwenye {{ $event->name }}. Uwepo wako utafanya tukio hili liwe la kipekee zaidi.</p>
                            <p class="mb-0">Tafadhali wasilisha tiketi hii kwenye ukumbi kwa ajili ya kuingia.</p>
                            <hr class="my-3">
                            <p class="mb-0 fst-italic">(We are delighted to invite you to {{ $event->name }}. Your presence will make this event even more special. Please present this ticket at the venue for entry.)</p>
                        </div>

                        <div class="ticket-qr text-center mb-4 p-3 border rounded bg-white">
                            {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                            <p class="text-muted mt-2 mb-0">Scan hii kwa ajili ya kuthibitisha tiketi</p>
                            <p class="text-muted small">(Scan this to verify the ticket)</p>
                        </div>

                        <div class="ticket-details p-4 border rounded">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Maelezo ya Mgeni</h6>
                                    <p><strong>Jina:</strong> {{ $ticket->attendee->name }}</p>
                                    <p><strong>Barua pepe:</strong> {{ $ticket->attendee->email }}</p>
                                    <p><strong>Simu:</strong> {{ $ticket->attendee->phone }}</p>
                                    @if($ticket->attendee->position)
                                        <p><strong>Cheo:</strong> {{ $ticket->attendee->position }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Maelezo ya Tiketi</h6>
                                    <p><strong>Token:</strong> {{ $ticket->token }}</p>
                                    @if($event->venue)
                                        <p><strong>Mahali:</strong> {{ $event->venue }}</p>
                                    @endif
                                    @if($event->start_time)
                                        <p><strong>Muda:</strong> {{ $event->start_time }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Regular ticket information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="invitation-card bg-gradient bg-light rounded p-4 mb-4 text-center border shadow-sm">
                                <h5 class="mb-3 text-primary">Karibu! (Welcome!)</h5>
                                <p class="mb-2">Mpendwa {{ $ticket->attendee->name }},</p>
                                <p class="mb-2">Tunafurahi kukualika kwenye {{ $event->name }}. Uwepo wako utafanya tukio hili liwe la kipekee zaidi.</p>
                                <p class="mb-0">Tafadhali wasilisha tiketi hii kwenye ukumbi kwa ajili ya kuingia.</p>
                                <hr class="my-3">
                                <p class="mb-0 fst-italic">(We are delighted to invite you to {{ $event->name }}. Your presence will make this event even more special. Please present this ticket at the venue for entry.)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Attendee Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name:</dt>
                                <dd class="col-sm-8">{{ $ticket->attendee->name }}</dd>

                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">{{ $ticket->attendee->email }}</dd>

                                <dt class="col-sm-4">Phone:</dt>
                                <dd class="col-sm-8">{{ $ticket->attendee->phone }}</dd>

                                @if($ticket->attendee->position)
                                    <dt class="col-sm-4">Position:</dt>
                                    <dd class="col-sm-8">{{ $ticket->attendee->position }}</dd>
                                @endif
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Event Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Event:</dt>
                                <dd class="col-sm-8">{{ $event->name }}</dd>

                                <dt class="col-sm-4">Date:</dt>
                                <dd class="col-sm-8">{{ $event->start_date->format('M d, Y') }}</dd>

                                @if($event->venue)
                                    <dt class="col-sm-4">Venue:</dt>
                                    <dd class="col-sm-8">{{ $event->venue }}</dd>
                                @endif

                                @if($event->start_time)
                                    <dt class="col-sm-4">Time:</dt>
                                    <dd class="col-sm-8">{{ $event->start_time }}</dd>
                                @endif

                                <dt class="col-sm-4">Token:</dt>
                                <dd class="col-sm-8">{{ $ticket->token }}</dd>
                            </dl>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div class="text-center mb-4">
                        <div class="qr-code-container p-3 bg-light rounded d-inline-block">
                            {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                        </div>
                        <p class="text-muted mt-2">Scan this QR code to view the ticket</p>
                    </div>

                    <!-- Scan History -->
                    @if($ticket->scanLogs->isNotEmpty())
                        <h6 class="text-muted mb-3">Scan History</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Scanner</th>
                                        <th>Time</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ticket->scanLogs as $log)
                                        <tr>
                                            <td>{{ $log->scanner->name }}</td>
                                            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                            <td>{{ $log->notes }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No scan history available for this ticket.
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-start">
                        <a href="{{ route('events.tickets', $event) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:styles>
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }
                #printableTicket, #printableTicket * {
                    visibility: visible;
                }
                #printableTicket {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    padding: 20px;
                }
                .ticket-qr {
                    margin: 2rem 0;
                }
                .ticket-details {
                    margin: 2rem 0;
                }
                .invitation-message {
                    margin: 2rem 0;
                    padding: 1.5rem;
                    border: 2px solid #dee2e6;
                    border-radius: 0.5rem;
                    background-color: #f8f9fa !important;
                }
                .text-primary {
                    color: #0d6efd !important;
                }
                .border {
                    border: 1px solid #dee2e6 !important;
                }
                .bg-light {
                    background-color: #f8f9fa !important;
                }
            }
            .qr-code-container {
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                background-color: white;
                padding: 1rem;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }
            .invitation-card {
                border: 1px solid #dee2e6;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
            }
            .event-logo {
                max-width: 150px;
                height: auto;
                margin-bottom: 1rem;
                filter: drop-shadow(0 0.125rem 0.25rem rgba(0, 0, 0, 0.075));
            }
            .bg-gradient {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            }
        </style>
    </x-slot>

    <x-slot:scripts>
        <script>
            function printTicket() {
                // Hide all elements except the printable ticket
                const elementsToHide = document.querySelectorAll('body > *:not(#printableTicket)');
                elementsToHide.forEach(el => el.style.display = 'none');
                
                // Show the printable ticket
                const printableTicket = document.getElementById('printableTicket');
                printableTicket.classList.remove('d-none');
                printableTicket.style.display = 'block';
                printableTicket.style.position = 'absolute';
                printableTicket.style.left = '0';
                printableTicket.style.top = '0';
                printableTicket.style.width = '100%';
                printableTicket.style.padding = '20px';
                printableTicket.style.backgroundColor = 'white';
                
                // Print
                window.print();
                
                // Restore the original display
                elementsToHide.forEach(el => el.style.display = '');
                printableTicket.classList.add('d-none');
                printableTicket.style = '';
            }

            function shareViaWhatsApp() {
                const text = `Ticket for {{ $event->name }}\n\n` +
                            `Attendee: {{ $ticket->attendee->name }}\n` +
                            `Token: {{ $ticket->token }}\n` +
                            `Event Date: {{ $event->start_date->format('M d, Y') }}\n\n` +
                            `View ticket: {{ route('tickets.verify', $ticket->token) }}`;
                
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
                window.open(whatsappUrl, '_blank');
            }

            function shareViaSMS() {
                const text = `Ticket for {{ $event->name }}\n\n` +
                            `Attendee: {{ $ticket->attendee->name }}\n` +
                            `Token: {{ $ticket->token }}\n` +
                            `Event Date: {{ $event->start_date->format('M d, Y') }}\n\n` +
                            `View ticket: {{ route('tickets.verify', $ticket->token) }}`;
                
                const smsUrl = `sms:?body=${encodeURIComponent(text)}`;
                window.location.href = smsUrl;
            }

            function copyTicketLink() {
                const ticketUrl = '{{ route('tickets.verify', $ticket->token) }}';
                navigator.clipboard.writeText(ticketUrl).then(() => {
                    alert('Ticket link copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy ticket link:', err);
                    alert('Failed to copy ticket link. Please try again.');
                });
            }
        </script>
    </x-slot>
</x-app-layout> 