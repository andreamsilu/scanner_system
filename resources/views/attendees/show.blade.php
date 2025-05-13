@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Attendee Details</h1>
        <div>
            <a href="{{ route('attendees.edit', $attendee) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Edit Attendee
            </a>
            <a href="{{ route('attendees.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Attendees
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
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
                            <p class="mb-0">{{ $attendee->registration_date->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
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
                            <label class="form-label text-muted">Ticket Type</label>
                            <p class="mb-0">{{ ucfirst($attendee->ticket_type) }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $attendee->status === 'confirmed' ? 'success' : ($attendee->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($attendee->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendees.destroy', $attendee) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2" onclick="return confirm('Are you sure you want to delete this attendee?')">
                            <i class="fas fa-trash"></i> Delete Attendee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 