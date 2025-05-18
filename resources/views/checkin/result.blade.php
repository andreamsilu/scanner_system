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