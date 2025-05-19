@extends('layouts.app')

@section('title', 'Add New Attendee')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Attendee</h1>
        <a href="{{ route('events.attendees', $event) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Attendees
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('events.attendees.store', $event) }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="registration_status" class="form-label">Registration Status</label>
                        <select class="form-select @error('registration_status') is-invalid @enderror" 
                                id="registration_status" name="registration_status" required>
                            <option value="registered" {{ old('registration_status') == 'registered' ? 'selected' : '' }}>Registered</option>
                            <option value="checked_in" {{ old('registration_status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="cancelled" {{ old('registration_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('registration_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Add Attendee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 