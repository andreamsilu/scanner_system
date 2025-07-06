@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Verify Event Card</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="h1 mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h4>Valid Event Card</h4>
                        <p class="text-muted">This event card belongs to:</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 150px;">Event:</th>
                                <td>{{ $attendee->event->name }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $attendee->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $attendee->email }}</td>
                            </tr>
                            @if($attendee->company)
                            <tr>
                                <th>Company:</th>
                                <td>{{ $attendee->company }}</td>
                            </tr>
                            @endif
                            @if($attendee->position)
                            <tr>
                                <th>Position:</th>
                                <td>{{ $attendee->position }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-{{ $attendee->status === 'registered' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($attendee->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- RSVP Section -->
                    @if($attendee->event->rsvp_enabled && ($attendee->event->rsvp_option_1_phone || $attendee->event->rsvp_option_2_phone))
                    <div class="mt-4 p-4" style="border: 2px solid #0d6efd; border-radius: 8px; background: rgba(13, 110, 253, 0.05);">
                        <h5 class="text-primary mb-3 text-center">RSVP - Tafadhali Jibu</h5>
                        
                        @if($attendee->event->rsvp_message)
                            <p class="mb-3 text-center" style="font-style: italic; color: #6c757d;">{{ $attendee->event->rsvp_message }}</p>
                        @endif
                        
                        <div class="row justify-content-center">
                            @if($attendee->event->rsvp_option_1_label && $attendee->event->rsvp_option_1_phone)
                            <div class="col-md-6 text-center mb-3">
                                <div class="p-3" style="border: 1px solid #dee2e6; border-radius: 8px; background: white;">
                                    <div class="text-primary fw-bold mb-2">{{ $attendee->event->rsvp_option_1_label }}</div>
                                    <div style="font-family: monospace; font-size: 1.1rem; color: #495057;">{{ $attendee->event->rsvp_option_1_phone }}</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($attendee->event->rsvp_option_2_label && $attendee->event->rsvp_option_2_phone)
                            <div class="col-md-6 text-center mb-3">
                                <div class="p-3" style="border: 1px solid #dee2e6; border-radius: 8px; background: white;">
                                    <div class="text-primary fw-bold mb-2">{{ $attendee->event->rsvp_option_2_label }}</div>
                                    <div style="font-family: monospace; font-size: 1.1rem; color: #495057;">{{ $attendee->event->rsvp_option_2_phone }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="text-center mt-4">
                        <p class="text-muted mb-3">Registration Date: {{ $attendee->registration_date->format('F j, Y g:i A') }}</p>
                        @if(auth()->check())
                            <form action="{{ route('scan-logs.store') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="attendee_id" value="{{ $attendee->id }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Mark as Attended
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 