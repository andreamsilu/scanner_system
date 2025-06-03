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
                                <i class="fas fa-print me-2"></i>Print Card
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
                                <div class="qr-container bg-white p-3 rounded-3 mb-4">
                                    {!! QrCode::size(200)->format('svg')->generate(route('event-cards.verify', $card->attendee->qr_token)) !!}
                                </div>
                                <p class="text-muted small mb-3">Scan this QR code at the venue</p>
                                <div class="ticket-info bg-light p-3 rounded-3">
                                    <p class="mb-2">
                                        <small class="text-muted d-block mb-1">Ticket ID</small>
                                        <span class="fw-medium">{{ $card->attendee->qr_token }}</span>
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted d-block mb-1">Status</small>
                                        @if($card->attendee->scanned)
                                            <span class="badge bg-success px-3 py-2">Confirmed</span>
                                        @else
                                            <span class="badge bg-warning px-3 py-2">Pending</span>
                                        @endif
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