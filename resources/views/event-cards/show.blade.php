@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-id-card me-2"></i>Event Card Details
                        </h5>
                        <a href="{{ route('event-cards.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Card Information -->
                        <div class="col-md-8">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <!-- Header Section -->
                                    <div class="text-center mb-4">
                                        <h3 class="text-primary mb-2">Sendoff Invitation</h3>
                                        <p class="text-muted">You are cordially invited to attend</p>
                                    </div>

                                    <!-- Guest Information -->
                                    <div class="row mb-4">
                                        <div class="col-md-3 text-center">
                                            <div class="avatar-circle bg-primary text-white mx-auto" style="width: 100px; height: 100px; font-size: 40px;">
                                                {{ strtoupper(substr($card->attendee->name, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <h4 class="mb-2">{{ $card->attendee->name }}</h4>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-briefcase me-2"></i>{{ $card->attendee->position ?? 'No Position' }}
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-building me-2"></i>{{ $card->attendee->company ?? 'No Company' }}
                                            </p>
                                            <div class="d-flex gap-2">
                                                @if($card->attendee->scanned)
                                                    <span class="badge bg-success px-3 py-2">
                                                        <i class="fas fa-check me-1"></i>Confirmed
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning px-3 py-2">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                @endif
                                                <span class="badge bg-info px-3 py-2">
                                                    <i class="fas fa-calendar me-1"></i>{{ $card->attendee->registration_date->format('M d, Y') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="contact-info p-3 bg-white rounded mb-3">
                                                <h6 class="text-primary mb-3">Contact Information</h6>
                                                <div class="mb-3">
                                                    <label class="text-muted d-block mb-1">
                                                        <i class="fas fa-envelope me-2"></i>Email
                                                    </label>
                                                    <p class="mb-0">{{ $card->attendee->email }}</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="text-muted d-block mb-1">
                                                        <i class="fas fa-phone me-2"></i>Phone
                                                    </label>
                                                    <p class="mb-0">{{ $card->attendee->phone ?? 'Not provided' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="event-info p-3 bg-white rounded">
                                                <h6 class="text-primary mb-3">Event Details</h6>
                                                <div class="mb-3">
                                                    <label class="text-muted d-block mb-1">
                                                        <i class="fas fa-map-marker-alt me-2"></i>Venue
                                                    </label>
                                                    <p class="mb-0">Main Conference Hall</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="text-muted d-block mb-1">
                                                        <i class="fas fa-clock me-2"></i>Time
                                                    </label>
                                                    <p class="mb-0">2:00 PM - 5:00 PM</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-primary mb-3">Entry Pass</h6>
                                    <div class="qr-container bg-white p-4 rounded mb-3">
                                        {!! QrCode::size(200)->format('svg')->generate(route('event-cards.verify', $card->attendee->qr_token)) !!}
                                    </div>
                                    <p class="text-muted small mb-2">Scan this QR code at the venue</p>
                                    <div class="ticket-info bg-white p-3 rounded">
                                        <p class="mb-1"><strong>Ticket ID:</strong> {{ $card->attendee->qr_token }}</p>
                                        <p class="mb-0"><strong>Status:</strong> 
                                            @if($card->attendee->scanned)
                                                <span class="text-success">Confirmed</span>
                                            @else
                                                <span class="text-warning">Pending</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="text-primary mb-3">Card Actions</h6>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('event-cards.edit', $card->attendee) }}" 
                                            class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i>Edit Invitation
                                        </a>
                                        <a href="{{ route('event-cards.download', $card->attendee) }}" 
                                            class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>Download PDF
                                        </a>
                                        <form action="{{ route('event-cards.destroy', $card->attendee) }}" 
                                            method="POST" 
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this invitation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash me-2"></i>Delete Invitation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border-radius: 10px;
    }
    .btn {
        border-radius: 8px;
        padding: 0.5rem 1rem;
    }
    .badge {
        border-radius: 6px;
        font-weight: 500;
    }
    .avatar-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    .contact-info, .event-info, .qr-container, .ticket-info {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .text-primary {
        color: #0d6efd !important;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
</style>
@endpush
@endsection 