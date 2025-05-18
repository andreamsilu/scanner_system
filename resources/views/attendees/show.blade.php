@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Attendee Details</h1>
        <div>
            <a href="{{ route('events.attendees.edit', ['event' => $attendee->event_id, 'attendee' => $attendee]) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Edit Attendee
            </a>
            <a href="{{ route('events.attendees', $attendee->event_id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Attendees
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Status Overview Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Status Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Ticket Status</label>
                            <p class="mb-0">
                                @if($attendee->ticket)
                                    <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'valid' ? 'primary' : 'warning') }}">
                                        {{ ucfirst($attendee->ticket->status) }}
                                    </span>
                                @else
                                    <span class="badge bg-danger">No Ticket</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Check-in Status</label>
                            <p class="mb-0">
                                @if($attendee->ticket && $attendee->ticket->status === 'used')
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i> Checked In
                                    </span>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i> Not Checked In
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Last Updated</label>
                            <p class="mb-0">
                                @if($attendee->ticket && $attendee->ticket->updated_at)
                                    {{ $attendee->ticket->updated_at->format('M d, Y H:i') }}
                                @else
                                    Never
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Name</label>
                            <p class="mb-0">{{ $attendee->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <p class="mb-0">{{ $attendee->email }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <p class="mb-0">{{ $attendee->phone }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Registration Date</label>
                            <p class="mb-0">{{ $attendee->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Information Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Event Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Event</label>
                            <p class="mb-0">{{ $attendee->event->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Event Date</label>
                            <p class="mb-0">{{ $attendee->event->start_date->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scan History Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Scan History</h5>
                </div>
                <div class="card-body">
                    @if($attendee->ticket && $attendee->ticket->scanLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                        <th>Scanner</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendee->ticket->scanLogs->sortByDesc('created_at') as $scan)
                                        <tr>
                                            <td>{{ $scan->created_at->format('M d, Y H:i:s') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($scan->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $scan->scanner ? $scan->scanner->name : 'Unknown' }}</td>
                                            <td>{{ $scan->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No scan history available</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($attendee->ticket && $attendee->ticket->status !== 'used')
                        <form action="{{ route('events.attendees.check-in', ['event' => $attendee->event_id, 'attendee' => $attendee]) }}" method="POST" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check-circle"></i> Check In Attendee
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('events.attendees.destroy', ['event' => $attendee->event_id, 'attendee' => $attendee]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this attendee?')">
                            <i class="fas fa-trash"></i> Delete Attendee
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ticket Information Card -->
            @if($attendee->ticket)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ticket Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Ticket ID</label>
                            <p class="mb-0">{{ $attendee->ticket->ticket_code }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Ticket Type</label>
                            <p class="mb-0">{{ ucfirst($attendee->ticket->type) }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Issued Date</label>
                            <p class="mb-0">{{ $attendee->ticket->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- QR Code Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">QR Code</h5>
                    <div class="btn-group">
                        @if($attendee->qr_code)
                            <a href="{{ $attendee->qr_code_url }}" 
                               download="qr-code-{{ $attendee->token }}.png" 
                               class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download"></i> Download QR Code
                            </a>
                        @endif
                        <form action="{{ route('events.attendees.regenerate-qr', ['event' => $event, 'attendee' => $attendee]) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Regenerate QR Code
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body text-center">
                    @if($attendee->qr_code)
                        <div class="qr-code-container mb-3">
                            <img src="{{ $attendee->qr_code_url }}" 
                                 alt="Attendee QR Code" 
                                 class="img-fluid" 
                                 style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        </div>
                        <div class="qr-code-info">
                            <p class="text-muted mb-1">Scan this QR code to validate the attendee's ticket</p>
                            <div class="token-info bg-light p-2 rounded d-inline-block">
                                <small class="text-muted">Token: <code>{{ $attendee->token }}</code></small>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No QR code generated. Click "Regenerate QR Code" to create one.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 