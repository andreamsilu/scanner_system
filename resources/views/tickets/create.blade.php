@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-ticket-perforated me-2"></i>Create Ticket for {{ $attendee->name }}
                        </h5>
                        <a href="{{ route('events.attendees.show', ['event' => $event, 'attendee' => $attendee]) }}" 
                           class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>Back to Attendee
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('events.tickets.store', ['event' => $event, 'attendee' => $attendee]) }}" 
                          method="POST">
                        @csrf

                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Attendee Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> {{ $attendee->name }}</p>
                                    <p><strong>Email:</strong> {{ $attendee->email }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Event:</strong> {{ $event->name }}</p>
                                    <p><strong>Token:</strong> {{ $attendee->token }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Ticket Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Ticket Type</label>
                                        <x-ticket-type-select 
                                            name="type" 
                                            :value="old('type', 'regular')"
                                            class="form-select @error('type') is-invalid @enderror" />
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-ticket-perforated me-2"></i>Create Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 