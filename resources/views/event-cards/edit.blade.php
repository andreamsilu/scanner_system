@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-edit me-2"></i>Edit Event Card
                        </h5>
                        <a href="{{ route('event-cards.show', $attendee) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Details
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('event-cards.update', $attendee) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="event-info bg-light rounded p-4 mb-4">
                            <h6 class="text-muted mb-3">Event Information</h6>
                            <h4 class="mb-0">{{ $attendee->event->name }}</h4>
                        </div>

                        <div class="attendee-details">
                            <h6 class="text-muted mb-3">Attendee Information</h6>
                            
                            <div class="mb-4">
                                <label for="name" class="form-label fw-medium">Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                        id="name" name="name" value="{{ old('name', $attendee->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                        id="email" name="email" value="{{ old('email', $attendee->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="phone" class="form-label fw-medium">Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-phone text-muted"></i>
                                    </span>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                        id="phone" name="phone" value="{{ old('phone', $attendee->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="company" class="form-label fw-medium">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-building text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('company') is-invalid @enderror" 
                                        id="company" name="company" value="{{ old('company', $attendee->company) }}">
                                    @error('company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="position" class="form-label fw-medium">Position</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-briefcase text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                        id="position" name="position" value="{{ old('position', $attendee->position) }}">
                                    @error('position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('event-cards.show', $attendee) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Event Card
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border-radius: 15px;
        overflow: hidden;
    }
    .form-control {
        border-radius: 8px;
        padding: 0.75rem 1rem;
    }
    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }
    .input-group-text {
        border-radius: 8px 0 0 8px;
        border: 1px solid #dee2e6;
    }
    .input-group .form-control {
        border-radius: 0 8px 8px 0;
    }
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }
    .btn-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca);
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(45deg, #0a58ca, #084298);
        transform: translateY(-1px);
    }
    .btn-outline-secondary:hover {
        transform: translateY(-1px);
    }
    .invalid-feedback {
        font-size: 0.875rem;
    }
</style>
@endpush
@endsection 