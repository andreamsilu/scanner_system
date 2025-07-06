@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-id-card me-2"></i>Event Cards Management
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Cards List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0">All Event Cards</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Attendee Name</th>
                                    <th>Event</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendees as $attendee)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <span class="text-white fw-bold">{{ strtoupper(substr($attendee->name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $attendee->name }}</h6>
                                                <small class="text-muted">Token: {{ $attendee->token }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($attendee->event)
                                            <span class="badge bg-info">{{ $attendee->event->name }}</span>
                                        @else
                                            <span class="text-muted">No Event</span>
                                        @endif
                                    </td>
                                    <td>{{ $attendee->email ?? 'N/A' }}</td>
                                    <td>{{ $attendee->phone ?? 'N/A' }}</td>
                                    <td>
                                        @if($attendee->registration_status === 'registered')
                                            <span class="badge bg-success">Registered</span>
                                        @elseif($attendee->registration_status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($attendee->registration_status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $attendee->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('event-cards.show', $attendee) }}" 
                                                class="btn btn-sm btn-outline-primary" 
                                                title="View Card">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('event-cards.download', $attendee) }}" 
                                                class="btn btn-sm btn-outline-success" 
                                                title="Download JPG">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="{{ route('event-cards.edit', $attendee) }}" 
                                                class="btn btn-sm btn-outline-warning" 
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('event-cards.destroy', $attendee) }}" 
                                                method="POST" 
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this event card?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-id-card fa-3x mb-3"></i>
                                            <h6>No Event Cards Found</h6>
                                            <p>Event cards will appear here once attendees are registered.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            @if($attendees->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $attendees->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
    }
    .btn-group .btn {
        border-radius: 0;
    }
    .btn-group .btn:first-child {
        border-top-left-radius: 0.375rem;
        border-bottom-left-radius: 0.375rem;
    }
    .btn-group .btn:last-child {
        border-top-right-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
    }
</style>
@endpush
@endsection 