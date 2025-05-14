@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Event Details</h5>
                        <div>
                            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                            <a href="{{ route('events.edit', $event) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Event
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($event->banner)
                        <div class="text-center mb-4">
                            <img src="{{ $event->banner_url }}" 
                                 alt="{{ $event->name }}" 
                                 class="img-fluid rounded" 
                                 style="max-height: 300px;">
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <h2 class="mb-3">{{ $event->name }}</h2>
                            
                            <div class="mb-4">
                                <h5 class="text-muted mb-3">Description</h5>
                                <p class="lead">{{ $event->description }}</p>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-3">Event Details</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            <strong>Start Date:</strong>
                                            {{ $event->start_date->format('F j, Y g:i A') }}
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-calendar-check text-primary me-2"></i>
                                            <strong>End Date:</strong>
                                            {{ $event->end_date->format('F j, Y g:i A') }}
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                            <strong>Venue:</strong>
                                            {{ $event->venue }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-3">Event Status</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-users text-primary me-2"></i>
                                            <strong>Capacity:</strong>
                                            {{ number_format($event->capacity) }} attendees
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-ticket-alt text-primary me-2"></i>
                                            <strong>Status:</strong>
                                            <span class="badge bg-{{ $event->status === 'published' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($event->status) }}
                                            </span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <strong>Created:</strong>
                                            {{ $event->created_at->format('F j, Y g:i A') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('event-cards.create', ['event' => $event->id]) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-qrcode me-2"></i>Generate Event Cards
                                        </a>
                                        <a href="{{ route('event-cards.index', ['event' => $event->id]) }}" 
                                           class="btn btn-outline-secondary">
                                            <i class="fas fa-list me-2"></i>View Event Cards
                                        </a>
                                        <a href="{{ route('activity-logs.index', ['event' => $event->id]) }}" 
                                           class="btn btn-outline-info">
                                            <i class="fas fa-history me-2"></i>View Activity Logs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scanner Assignment Section -->
            @include('events.partials.scanner-assignment')
        </div>
    </div>
</div>
@endsection 