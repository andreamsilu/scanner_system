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
                            <th>Ticket ID</th>
                            <th>Event</th>
                            <th>Attendee</th>
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
                                <td>{{ $ticket->ticket_id }}</td>
                                <td>
                                    <a href="{{ route('events.show', $ticket->event) }}">
                                        {{ $ticket->event->name }}
                                    </a>
                                </td>
                                <td>
                                    @if($ticket->attendee)
                                        <a href="{{ route('attendees.show', $ticket->attendee) }}">
                                            {{ $ticket->attendee->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
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
                                        <a href="{{ route('tickets.print', $ticket) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Print Ticket"
                                           target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
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
</script>
@endpush 