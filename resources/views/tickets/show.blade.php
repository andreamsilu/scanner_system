<x-app-layout>
    <x-slot:title>
        Ticket Details
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Ticket Details</h5>
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
                            <h4 class="mb-2">{{ $event->name }}</h4>
                            <p class="mb-0">{{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}</p>
                        </div>

                        <div class="invitation-message text-center mb-4">
                            <h5 class="mb-3">You're Invited!</h5>
                            <p class="mb-2">Dear {{ $ticket->attendee->name }},</p>
                            <p class="mb-2">We are delighted to invite you to {{ $event->name }}. Your presence will make this event even more special.</p>
                            <p class="mb-0">Please present this ticket at the venue for entry.</p>
                        </div>

                        <div class="ticket-qr text-center mb-4">
                            {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                        </div>
                        <div class="ticket-details">
                            <p><strong>Attendee:</strong> {{ $ticket->attendee->name }}</p>
                            <p><strong>Token:</strong> {{ $ticket->token }}</p>
                            @if($event->venue)
                                <p><strong>Venue:</strong> {{ $event->venue }}</p>
                            @endif
                            @if($event->start_time)
                                <p><strong>Time:</strong> {{ $event->start_time }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Regular ticket information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="invitation-card bg-light rounded p-4 mb-4 text-center">
                                <h5 class="mb-3">You're Invited!</h5>
                                <p class="mb-2">Dear {{ $ticket->attendee->name }},</p>
                                <p class="mb-2">We are delighted to invite you to {{ $event->name }}. Your presence will make this event even more special.</p>
                                <p class="mb-0">Please present this ticket at the venue for entry.</p>
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
                }
                .ticket-qr {
                    margin: 2rem 0;
                }
                .ticket-details {
                    margin: 2rem 0;
                }
                .invitation-message {
                    margin: 2rem 0;
                    padding: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 0.5rem;
                }
            }
            .qr-code-container {
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
            }
            .invitation-card {
                border: 1px solid #dee2e6;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }
            .event-logo {
                max-width: 150px;
                height: auto;
                margin-bottom: 1rem;
            }
        </style>
    </x-slot>

    <x-slot:scripts>
        <script>
            function printTicket() {
                window.print();
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