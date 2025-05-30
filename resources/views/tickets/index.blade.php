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
                                        <div>
                                            <span>{{ $ticket->attendee->name }}</span>
                                            <br>
                                            <small class="text-muted">ID: {{ $ticket->token }}</small>
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
                                        <a href="{{ route('events.events.tickets.show', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                title="Print Ticket"
                                                onclick="printTicket('{{ route('events.events.tickets.print', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}')">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                        <a href="{{ route('events.events.tickets.export', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}"
                                           class="btn btn-sm btn-danger"
                                           title="Download PDF"
                                           target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
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
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('events.events.tickets.export', ['event' => $ticket->event->id, 'ticket' => $ticket->token]) }}" target="_blank">
                                                    <i class="bi bi-file-pdf me-2"></i> Download as PDF
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
            order: [[5, 'desc']], // Sort by created_at by default
            dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="bi bi-download me-2"></i>Export',
                    className: 'btn btn-primary',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                            className: 'btn btn-success',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5] // Exclude actions column
                            },
                            title: 'Tickets List',
                            messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                            className: 'btn btn-danger',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5] // Exclude actions column
                            },
                            title: 'Tickets List',
                            messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                            customize: function(doc) {
                                doc.defaultStyle.fontSize = 10;
                                doc.styles.tableHeader.fontSize = 11;
                                doc.styles.tableHeader.fillColor = '#f8f9fa';
                                doc.styles.tableHeader.color = '#212529';
                                doc.pageMargins = [40, 60, 40, 60];
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer me-2"></i>Print',
                            className: 'btn btn-info',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5] // Exclude actions column
                            },
                            title: 'Tickets List',
                            messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                        }
                    ]
                }
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