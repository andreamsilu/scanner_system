@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Attendee</h1>
        <a href="{{ route('attendees.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Attendees
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('attendees.update', $attendee) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $attendee->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $attendee->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $attendee->phone) }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="event_id" class="form-label">Event</label>
                        <select class="form-select @error('event_id') is-invalid @enderror" id="event_id" name="event_id" required>
                            <option value="">Select Event</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id', $attendee->event_id) == $event->id ? 'selected' : '' }}>
                                    {{ $event->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ticket_type" class="form-label">Ticket Type</label>
                        <select class="form-select @error('ticket_type') is-invalid @enderror" id="ticket_type" name="ticket_type" required>
                            <option value="">Select Ticket Type</option>
                            <option value="regular" {{ old('ticket_type', $attendee->ticket_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="vip" {{ old('ticket_type', $attendee->ticket_type) == 'vip' ? 'selected' : '' }}>VIP</option>
                            <option value="early_bird" {{ old('ticket_type', $attendee->ticket_type) == 'early_bird' ? 'selected' : '' }}>Early Bird</option>
                        </select>
                        @error('ticket_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="pending" {{ old('status', $attendee->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ old('status', $attendee->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="cancelled" {{ old('status', $attendee->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Attendee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 