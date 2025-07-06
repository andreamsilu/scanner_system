@extends('layouts.app')

@section('title', 'Attendee Check-In')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-body text-center">
                    @if($success)
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            <h2 class="text-success mt-2">Check-in Successful!</h2>
                        </div>
                        <p class="lead">Welcome, <strong>{{ $attendee->name }}</strong>!</p>
                        @if($attendee->event)
                            <p>Event: <strong>{{ $attendee->event->name }}</strong></p>
                        @endif
                        <p class="mb-2">Your ticket has been checked in. Enjoy the event!</p>
                        
                        <!-- RSVP Section -->
                        @if($attendee->event && $attendee->event->rsvp_enabled && ($attendee->event->rsvp_option_1_phone || $attendee->event->rsvp_option_2_phone))
                        <div class="mt-4 p-4" style="border: 2px solid #0d6efd; border-radius: 8px; background: rgba(13, 110, 253, 0.05);">
                            <h5 class="text-primary mb-3">RSVP - Tafadhali Jibu</h5>
                            
                            @if($attendee->event->rsvp_message)
                                <p class="mb-3" style="font-style: italic; color: #6c757d;">{{ $attendee->event->rsvp_message }}</p>
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
                        
                        <div class="mt-4">
                            <a href="/" class="btn btn-primary">Back to Home</a>
                        </div>
                    @else
                        <div class="mb-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                            <h2 class="text-danger mt-2">Check-in Failed</h2>
                        </div>
                        <p class="lead">{{ $message }}</p>
                        @if(isset($attendee) && $attendee)
                            <p>Name: <strong>{{ $attendee->name }}</strong></p>
                            @if($attendee->event)
                                <p>Event: <strong>{{ $attendee->event->name }}</strong></p>
                            @endif
                        @endif
                        <div class="mt-4">
                            <a href="/" class="btn btn-secondary">Back to Home</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 