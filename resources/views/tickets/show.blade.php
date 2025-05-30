@extends('layouts.app')

@section('title', 'Ticket Details')

@section('content')
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
                            <h4>{{ $event->name }}</h4>
                            <p class="mb-0">{{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="ticket-qr text-center mb-4">
                            {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                        </div>
                        <div class="ticket-details">
                            <p><strong>Attendee:</strong> {{ $ticket->attendee->name }}</p>
                            <p><strong>Ticket ID:</strong> {{ $ticket->token }}</p>
                            <p><strong>Status:</strong> {{ $ticket->is_valid ? 'Valid' : 'Invalid' }}</p>
                        </div>
                    </div>

                    <!-- Regular ticket information -->
                    <div class="row mb-4">
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
                            <h6 class="text-muted mb-3">Ticket Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Ticket ID:</dt>
                                <dd class="col-sm-8">{{ $ticket->token }}</dd>

                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-{{ $ticket->is_valid ? 'success' : 'danger' }}">
                                        {{ $ticket->is_valid ? 'Valid' : 'Invalid' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $ticket->created_at->format('M d, Y H:i') }}</dd>

                                @if($ticket->last_scanned_at)
                                    <dt class="col-sm-4">Last Scanned:</dt>
                                    <dd class="col-sm-8">{{ $ticket->last_scanned_at->format('M d, Y H:i') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div class="text-center mb-4">
                        <div class="qr-code-container p-3 bg-light rounded d-inline-block">
                            {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                        </div>
                        <p class="text-muted mt-2">Scan this QR code to verify the ticket</p>
                    </div>

                    <!-- Scan History -->
                    @if($ticket->scanLogs->isNotEmpty())
                        <h6 class="text-muted mb-3">Scan History</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Scanner</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ticket->scanLogs as $log)
                                        <tr>
                                            <td>{{ $log->scanner->name }}</td>
                                            <td>
                                                <span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($log->status) }}
                                                </span>
                                            </td>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('events.tickets.index', $event) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                        </a>
                        @if($ticket->is_valid)
                            <form action="{{ route('events.tickets.invalidate', [$event, $ticket]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to invalidate this ticket?')">
                                    <i class="fas fa-ban me-1"></i> Invalidate Ticket
                                </button>
                            </form>
                        @else
                            <form action="{{ route('events.tickets.validate', [$event, $ticket]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to validate this ticket?')">
                                    <i class="fas fa-check me-1"></i> Validate Ticket
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
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
        }
        .qr-code-container {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function printTicket() {
            window.print();
        }

        function shareViaWhatsApp() {
            const text = `Ticket for {{ $event->name }}\n\n` +
                        `Attendee: {{ $ticket->attendee->name }}\n` +
                        `Ticket ID: {{ $ticket->token }}\n` +
                        `Event Date: {{ $event->start_date->format('M d, Y') }}\n\n` +
                        `View ticket: {{ route('tickets.verify', $ticket->token) }}`;
            
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(whatsappUrl, '_blank');
        }

        function shareViaSMS() {
            const text = `Ticket for {{ $event->name }}\n\n` +
                        `Attendee: {{ $ticket->attendee->name }}\n` +
                        `Ticket ID: {{ $ticket->token }}\n` +
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
    @endpush
@endsection 