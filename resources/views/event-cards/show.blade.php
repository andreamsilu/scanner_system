@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <!-- Card Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-id-card me-2"></i>Sendoff Invitation Card
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('event-cards.download', $card->attendee) }}" 
                                class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>Download Card
                            </a>
                            <a href="{{ route('event-cards.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Card Content -->
            <div class="row">
                <!-- Invitation Card -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- Header -->
                            <div class="text-center mb-5">
                                <h1 class="text-primary mb-0 fw-bold" style="font-size: 2.5rem;">MWALIKO WA SENDOFF</h1>
                            </div>

                            <!-- Invitation Content -->
                            <div class="invitation-content text-center">
                                <div class="mb-4">
                                    <p class="mb-3" style="font-size: 1.1rem;">
                                        Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                                        wanayo furaha kubwa kukualika / kuwaalika
                                    </p>
                                    <p class="mb-4" style="font-size: 1.1rem;">
                                        Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                                    </p>
                                </div>

                                <!-- Guest Name -->
                                <div class="guest-name mb-5">
                                    <p class="mb-2" style="font-size: 1.2rem;">Jina la Mwalikwa:</p>
                                    <div class="underline-text mx-auto" style="max-width: 500px;">
                                        {{ $card->attendee->name }}
                                    </div>
                                </div>

                                <!-- Event Details -->
                                <div class="event-details">
                                    <p class="mb-3" style="font-size: 1.1rem;">
                                        Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                                        <strong class="text-primary" style="font-size: 1.3rem;">ALICE PIENZI CHAMBILA</strong>
                                    </p>
                                    <p class="mb-3" style="font-size: 1.1rem;">
                                        Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                                        Kuanzia saa 12:00 jioni
                                    </p>
                                    <p class="mb-3" style="font-size: 1.1rem;">
                                        Katika ukumbi wa GBF Garden Hall,<br>
                                        uliopo Dar es Salaam – Tabata, Barakuda.
                                    </p>
                                    <p class="mb-0" style="font-size: 1.1rem;">
                                        Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                                    </p>
                                </div>

                                <!-- RSVP Section -->
                                @if($card->attendee->event->rsvp_enabled && ($card->attendee->event->rsvp_option_1_phone || $card->attendee->event->rsvp_option_2_phone))
                                <div class="rsvp-section mt-5 p-4" style="border: 2px solid #0d6efd; border-radius: 12px; background: rgba(13, 110, 253, 0.05);">
                                    <h4 class="text-primary mb-3 text-center" style="font-size: 1.3rem;">RSVP - Tafadhali Jibu</h4>
                                    
                                    @if($card->attendee->event->rsvp_message)
                                        <p class="mb-3 text-center" style="font-style: italic; color: #6c757d;">{{ $card->attendee->event->rsvp_message }}</p>
                                    @endif
                                    
                                    <div class="row justify-content-center">
                                        @if($card->attendee->event->rsvp_option_1_label && $card->attendee->event->rsvp_option_1_phone)
                                        <div class="col-md-6 text-center mb-3">
                                            <div class="rsvp-option p-3" style="border: 1px solid #dee2e6; border-radius: 8px; background: white;">
                                                <div class="text-primary fw-bold mb-2">{{ $card->attendee->event->rsvp_option_1_label }}</div>
                                                <div class="phone-number" style="font-family: monospace; font-size: 1.1rem; color: #495057;">{{ $card->attendee->event->rsvp_option_1_phone }}</div>
                                            </div>
                                        </div>
                                        @endif
                                        
                                        @if($card->attendee->event->rsvp_option_2_label && $card->attendee->event->rsvp_option_2_phone)
                                        <div class="col-md-6 text-center mb-3">
                                            <div class="rsvp-option p-3" style="border: 1px solid #dee2e6; border-radius: 8px; background: white;">
                                                <div class="text-primary fw-bold mb-2">{{ $card->attendee->event->rsvp_option_2_label }}</div>
                                                <div class="phone-number" style="font-family: monospace; font-size: 1.1rem; color: #495057;">{{ $card->attendee->event->rsvp_option_2_phone }}</div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="text-center">
                                <h6 class="text-primary mb-4">Entry Pass</h6>
                                <!-- Ticket Content -->
                                <div class="ticket-content bg-white p-3 rounded-3 mb-4 text-start" style="font-size: 0.9rem; line-height: 1.6;">
                                    <h5 class="text-primary text-center mb-3 fw-bold">MWALIKO WA SENDOFF</h5>
                                    <p class="mb-2">
                                        Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                                        wanayo furaha kubwa kukualika / kuwaalika
                                    </p>
                                    <p class="mb-2">
                                        Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                                    </p>
                                    <div class="text-center mb-2">
                                        ..............................................................................................
                                    </div>
                                    <p class="mb-2">
                                        Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                                        <strong>ALICE PIENZI CHAMBILA</strong>
                                    </p>
                                    <p class="mb-2">
                                        Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                                        Kuanzia saa 12:00 jioni
                                    </p>
                                    <p class="mb-2">
                                        Katika ukumbi wa GBF Garden Hall,<br>
                                        uliopo Dar es Salaam – Tabata, Barakuda.
                                    </p>
                                    <p class="mb-0">
                                        Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                                    </p>
                                </div>
                                <!-- QR Code -->
                                <div class="qr-container bg-white p-3 rounded-3 mb-4">
                                    {!! QrCode::size(200)->format('svg')->generate(route('event-cards.verify', $card->attendee->qr_token)) !!}
                                </div>
                                <p class="text-muted small mb-3">Scan this QR code at the venue</p>
                                <div class="ticket-info bg-light p-3 rounded-3">
                                    <p class="mb-0">
                                        <small class="text-muted d-block mb-1">Ticket ID</small>
                                        <span class="fw-medium">{{ $card->attendee->qr_token }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-4">
                            <h6 class="text-primary mb-3">Card Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="{{ route('event-cards.download', $card->attendee) }}" 
                                    class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>Download JPG
                                </a>
                                <a href="{{ route('event-cards.edit', $card->attendee) }}" 
                                    class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Invitation
                                </a>
                                <form action="{{ route('event-cards.destroy', $card->attendee) }}" 
                                    method="POST" 
                                    class="d-grid"
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

@push('styles')
<style>
    .card {
        border-radius: 12px;
    }
    .btn {
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }
    .badge {
        border-radius: 6px;
        font-weight: 500;
    }
    .text-primary {
        color: #0d6efd !important;
    }
    .invitation-content {
        font-family: 'Arial', sans-serif;
    }
    .underline-text {
        border-bottom: 2px solid #dee2e6;
        min-height: 2.5rem;
        padding: 0.5rem;
        font-size: 1.2rem;
    }
    .qr-container {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .ticket-info {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .guest-name {
        font-size: 1.2rem;
    }
    .shadow-sm {
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    }
</style>
@endpush
@endsection 