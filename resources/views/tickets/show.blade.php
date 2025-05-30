@extends('layouts.app')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ticket Details</h1>
        <div>
            <a href="{{ route('events.tickets.index', $ticket->event) }}" class="btn btn-secondary">
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
                <div class="card-header">
                    <h5 class="card-title mb-0">Ticket Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="text-primary mb-3">{{ $ticket->attendee->name }}</h4>
                            @if($ticket->attendee->email)
                                <p class="mb-1">
                                    <i class="bi bi-envelope me-2"></i>
                                    {{ $ticket->attendee->email }}
                                </p>
                            @endif
                            @if($ticket->attendee->phone)
                                <p class="mb-1">
                                    <i class="bi bi-telephone me-2"></i>
                                    {{ $ticket->attendee->phone }}
                                </p>
                            @endif
                            <p class="mb-1">
                                <i class="bi bi-ticket-detailed me-2"></i>
                                Token: {{ $ticket->token }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column align-items-end">
                                <div class="mb-2">
                                    @if($ticket->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                                <div class="mb-2">
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
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucfirst($ticketType) }}
                                    </span>
                                </div>
                                <p class="text-muted mb-0">
                                    Created: {{ $ticket->created_at->format('M j, Y g:i A') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">Event Information</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th style="width: 200px;">Event Name</th>
                                            <td>
                                                <a href="{{ route('events.show', $ticket->event) }}">
                                                    {{ $ticket->event->name }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Event Date</th>
                                            <td>{{ $ticket->event->start_date->format('F j, Y g:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Venue</th>
                                            <td>{{ $ticket->event->venue }}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Scan</th>
                                            <td>
                                                @if($ticket->last_scan)
                                                    <span title="{{ $ticket->last_scan->created_at->format('F j, Y g:i A') }}">
                                                        {{ $ticket->last_scan->created_at->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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
                                onclick="printTicket('{{ route('events.tickets.print', [$ticket->event, $ticket]) }}')">
                            <i class="bi bi-printer me-2"></i> Print Ticket
                        </button>
                        <a href="{{ route('events.tickets.export', [$ticket->event, $ticket]) }}" 
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
                            <form action="{{ route('events.tickets.deactivate', [$ticket->event, $ticket]) }}" 
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