@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-id-card me-2"></i>Sendoff Invitation Card
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
                                        <h2 class="text-primary mb-3 fw-bold">MWALIKO WA SENDOFF</h2>
                                    </div>

                                    <!-- Invitation Content -->
                                    <div class="invitation-content text-center mb-4">
                                        <p class="mb-3">
                                            Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                                            wanayo furaha kubwa kukualika / kuwaalika
                                        </p>
                                        <p class="mb-3">
                                            Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                                        </p>
                                        <div class="guest-name mb-4">
                                            <div class="underline-text mb-3">
                                                {{ $card->attendee->name }}
                                            </div>
                                        </div>
                                        <p class="mb-3">
                                            Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                                            <strong>ALICE PIENZI CHAMBILA</strong>
                                        </p>
                                        <p class="mb-3">
                                            Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                                            Kuanzia saa 12:00 jioni
                                        </p>
                                        <p class="mb-3">
                                            Katika ukumbi wa GBF Garden Hall,<br>
                                            uliopo Dar es Salaam – Tabata, Barakuda.
                                        </p>
                                        <p class="mb-3">
                                            Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                                        </p>
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
    .text-primary {
        color: #0d6efd !important;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
    .invitation-content {
        font-size: 1.1rem;
        line-height: 1.8;
    }
    .underline-text {
        border-bottom: 1px solid #dee2e6;
        min-height: 2rem;
        padding: 0.5rem;
    }
    .contact-info, .venue-info, .qr-container, .ticket-info {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .guest-name {
        font-size: 1.2rem;
    }
</style>
@endpush
@endsection 