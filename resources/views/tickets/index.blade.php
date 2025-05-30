@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tickets</h1>
        <div>
            <a href="{{ route('events.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Events
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tickets-table">
                    <thead>
                        <tr>
                            <th>Attendee Name</th>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Last Scan</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>
                                    @if($ticket->attendee)
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('events.attendees.show', ['event' => $ticket->event, 'attendee' => $ticket->attendee]) }}" 
                                               class="text-primary fw-semibold text-decoration-none">
                                                {{ $ticket->attendee->name }}
                                            </a>
                                            @if($ticket->attendee->email)
                                                <small class="text-muted">{{ $ticket->attendee->email }}</small>
                                            @endif
                                            @if($ticket->attendee->phone)
                                                <small class="text-muted">{{ $ticket->attendee->phone }}</small>
                                            @endif
                                            <small class="text-muted mt-1">Token: {{ $ticket->token }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('events.show', $ticket->event) }}">
                                        {{ $ticket->event->name }}
                                    </a>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    @if($ticket->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->last_scan)
                                        <span title="{{ $ticket->last_scan->created_at->format('F j, Y g:i A') }}">
                                            {{ $ticket->last_scan->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>{{ $ticket->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('tickets.show', $ticket) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                title="Print Ticket"
                                                onclick="printTicket('{{ route('tickets.print', $ticket) }}')">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-success" 
                                                title="Share Ticket"
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="bi bi-share"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
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
                                        @if($ticket->is_active)
                                            <form action="{{ route('tickets.deactivate', $ticket) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-warning" 
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
                                <td colspan="8" class="text-center">No tickets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#tickets-table').DataTable({
            processing: true,
            pageLength: 15,
            order: [[6, 'desc']], // Sort by created_at by default
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
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