@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Edit Attendee</h2>
        <a href="{{ route('attendees.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('attendees.update', $attendee) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="event_id" class="form-label">Event</label>
                        <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror" required>
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
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $attendee->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $attendee->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone', $attendee->phone) }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company" class="form-label">Company</label>
                        <input type="text" class="form-control @error('company') is-invalid @enderror" 
                               id="company" name="company" value="{{ old('company', $attendee->company) }}">
                        @error('company')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control @error('position') is-invalid @enderror" 
                               id="position" name="position" value="{{ old('position', $attendee->position) }}">
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="ticket_type" class="form-label">Ticket Type</label>
                        <select name="ticket_type" id="ticket_type" class="form-select @error('ticket_type') is-invalid @enderror" required>
                            <option value="regular" {{ old('ticket_type', $attendee->ticket_type) === 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="vip" {{ old('ticket_type', $attendee->ticket_type) === 'vip' ? 'selected' : '' }}>VIP</option>
                            <option value="early_bird" {{ old('ticket_type', $attendee->ticket_type) === 'early_bird' ? 'selected' : '' }}>Early Bird</option>
                        </select>
                        @error('ticket_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="registration_date" class="form-label">Registration Date</label>
                        <input type="datetime-local" class="form-control @error('registration_date') is-invalid @enderror" 
                               id="registration_date" name="registration_date" 
                               value="{{ old('registration_date', $attendee->registration_date->format('Y-m-d\TH:i')) }}" required>
                        @error('registration_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="registered" {{ old('status', $attendee->status) === 'registered' ? 'selected' : '' }}>Registered</option>
                            <option value="checked_in" {{ old('status', $attendee->status) === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="cancelled" {{ old('status', $attendee->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Attendee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 