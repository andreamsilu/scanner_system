@extends('layouts.app')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ticket Details</h1>
        <div>
            <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tickets
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="ticket" style="max-width: 800px; margin: 0 auto; border: 2px solid #000; border-radius: 10px; padding: 20px; position: relative; background: #fff;">
                        <div class="watermark" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 72px; color: rgba(0, 0, 0, 0.1); white-space: nowrap; pointer-events: none;">EVENT TICKET</div>
                        
                        <div class="ticket-header" style="text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px dashed #000;">
                            <h1 class="event-name" style="font-size: 24px; font-weight: bold; margin: 0; color: #000;">{{ $ticket->event->name }}</h1>
                            <div class="event-details" style="font-size: 16px; color: #666; margin: 10px 0;">
                                <p>{{ $ticket->event->venue }}</p>
                                <p>{{ $ticket->event->start_date->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>

                        <div class="welcome-message" style="text-align: center; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; border: 1px dashed #000;">
                            <h3 style="color: #0d6efd; margin-bottom: 15px;">Karibu! (Welcome!)</h3>
                            <p style="margin-bottom: 10px;">Mpendwa {{ $ticket->attendee->name }},</p>
                            <p style="margin-bottom: 10px;">Tunafurahi kukualika kwenye {{ $ticket->event->name }}. Uwepo wako utafanya tukio hili liwe la kipekee zaidi.</p>
                            <p style="margin-bottom: 15px;">Tafadhali wasilisha tiketi hii kwenye ukumbi kwa ajili ya kuingia.</p>
                            <hr style="margin: 15px 0; border-top: 1px dashed #000;">
                            <p style="font-style: italic; color: #666; margin: 0;">
                                (We are delighted to invite you to {{ $ticket->event->name }}. Your presence will make this event even more special. Please present this ticket at the venue for entry.)
                            </p>
                        </div>

                        <div class="ticket-content" style="display: flex; justify-content: space-between; margin: 20px 0;">
                            <div class="ticket-info" style="flex: 1; padding-right: 20px;">
                                <div class="attendee-info">
                                    <h3>Attendee Information</h3>
                                    <p><strong>Name:</strong> {{ $ticket->attendee->name }}</p>
                                    <p><strong>Email:</strong> {{ $ticket->attendee->email }}</p>
                                    @if($ticket->attendee->phone)
                                        <p><strong>Phone:</strong> {{ $ticket->attendee->phone }}</p>
                                    @endif
                                </div>

                                <div class="ticket-details">
                                    <h3>Ticket Details</h3>
                                    <p><strong>Ticket ID:</strong> <span class="ticket-id" style="font-family: monospace; font-size: 18px; background: #f5f5f5; padding: 5px 10px; border-radius: 5px; display: inline-block; margin: 10px 0;">{{ $ticket->token }}</span></p>
                                    <p><strong>Type:</strong> <span class="ticket-type {{ $ticket->type }}" style="display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin: 10px 0; 
                                        @php
                                            $bgColor = match($ticket->type) {
                                                'vip' => '#ffd700',
                                                'regular' => '#e0e0e0',
                                                'single' => '#90caf9',
                                                'double' => '#a5d6a7',
                                                default => '#e0e0e0'
                                            };
                                        @endphp
                                        background: {{ $bgColor }};">{{ ucfirst($ticket->type) }}</span></p>
                                    <p><strong>Status:</strong> {{ $ticket->is_active ? 'Active' : 'Inactive' }}</p>
                                    <p><strong>Issued:</strong> {{ $ticket->created_at->format('F j, Y g:i A') }}</p>
                                </div>
                            </div>

                            <div class="qr-code" style="flex: 1; text-align: center;">
                                @if($ticket->attendee->qr_code)
                                    <img src="{{ $ticket->attendee->qr_code_url }}" 
                                         alt="QR Code" 
                                         style="max-width: 200px; height: auto;">
                                    <p>Scan this QR code for check-in</p>
                                @else
                                    {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                                    <p>Scan this QR code for check-in</p>
                                @endif
                            </div>
                        </div>

                        <div class="ticket-footer" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #000; font-size: 12px; color: #666;">
                            <p>This ticket is non-transferable and must be presented at the event.</p>
                            <p>For any questions, please contact the event organizer.</p>
                            <p>Printed on {{ now()->format('F j, Y g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" 
                                class="btn btn-primary" 
                                onclick="printTicket('{{ route('tickets.print', $ticket) }}')">
                            <i class="bi bi-printer me-2"></i> Print Ticket
                        </button>
                        <a href="{{ route('tickets.export', $ticket) }}" 
                           class="btn btn-danger" 
                           target="_blank">
                            <i class="bi bi-file-pdf me-2"></i> Download PDF
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-success w-100" 
                                    type="button" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="bi bi-share me-2"></i> Share Ticket
                            </button>
                            <ul class="dropdown-menu w-100">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="shareViaWhatsApp('{{ $ticket->event->name }}', '{{ $ticket->attendee->name }}', '{{ $ticket->token }}', '{{ $ticket->event->start_date->format('M d, Y') }}', '{{ route('tickets.verify', $ticket->token) }}')">
                                        <i class="fab fa-whatsapp me-2"></i> Share via WhatsApp
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="shareViaSMS('{{ $ticket->event->name }}', '{{ $ticket->attendee->name }}', '{{ $ticket->token }}', '{{ $ticket->event->start_date->format('M d, Y') }}', '{{ route('tickets.verify', $ticket->token) }}')">
                                        <i class="fas fa-sms me-2"></i> Share via SMS
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="copyTicketLink('{{ route('tickets.verify', $ticket->token) }}')">
                                        <i class="fas fa-link me-2"></i> Copy Ticket Link
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @if($ticket->is_active)
                            <form action="{{ route('tickets.deactivate', $ticket) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-x-circle me-2"></i> Deactivate Ticket
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if($ticket->last_scan)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Last Scan Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>Scanned At</th>
                                        <td>{{ $ticket->last_scan->created_at->format('F j, Y g:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Scanned By</th>
                                        <td>{{ $ticket->last_scan->scanner->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($ticket->last_scan->is_valid)
                                                <span class="badge bg-success">Valid</span>
                                            @else
                                                <span class="badge bg-danger">Invalid</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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