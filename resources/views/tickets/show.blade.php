@extends('layouts.app')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Ticket Details</h1>
            <p class="text-muted">Ticket ID: {{ $ticket->ticket_id }}</p>
        </div>
        <div>
            <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tickets
            </a>
            <a href="{{ route('tickets.print', $ticket) }}" 
               class="btn btn-primary" 
               target="_blank">
                <i class="bi bi-printer"></i> Print Ticket
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ticket Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Event</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('events.show', $ticket->event) }}">
                                {{ $ticket->event->name }}
                            </a>
                        </dd>

                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8">
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
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            @if($ticket->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Created At</dt>
                        <dd class="col-sm-8">{{ $ticket->created_at->format('F j, Y g:i A') }}</dd>

                        <dt class="col-sm-4">Last Scan</dt>
                        <dd class="col-sm-8">
                            @if($ticket->last_scan)
                                <span title="{{ $ticket->last_scan->created_at->format('F j, Y g:i A') }}">
                                    {{ $ticket->last_scan->created_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendee Information</h5>
                </div>
                <div class="card-body">
                    @if($ticket->attendee)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('attendees.show', $ticket->attendee) }}">
                                    {{ $ticket->attendee->name }}
                                </a>
                            </dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $ticket->attendee->email }}</dd>

                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">{{ $ticket->attendee->phone ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Registration Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $ticket->attendee->registration_status === 'checked_in' ? 'success' : ($ticket->attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                    {{ ucfirst($ticket->attendee->registration_status) }}
                                </span>
                            </dd>

                            <dt class="col-sm-4">Registered At</dt>
                            <dd class="col-sm-8">
                                {{ $ticket->attendee->registered_at ? $ticket->attendee->registered_at->format('F j, Y g:i A') : 'N/A' }}
                            </dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">This ticket is not assigned to any attendee.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Scan History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Scanner</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Scanned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticket->scans as $scan)
                            <tr>
                                <td>{{ $scan->scanner->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                        {{ ucfirst($scan->status) }}
                                    </span>
                                </td>
                                <td>{{ $scan->notes ?? 'N/A' }}</td>
                                <td>{{ $scan->created_at->format('F j, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No scan history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 