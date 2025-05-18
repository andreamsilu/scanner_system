@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h3 mb-0">
                        @if(auth()->user()->hasRole('admin'))
                            Available Events
                        @else
                            Your Assigned Events
                        @endif
                    </h2>
                    <p class="text-muted mb-0">
                        @if(auth()->user()->hasRole('admin'))
                            Select any event to start scanning attendees
                        @else
                            Select an event to start scanning attendees
                        @endif
                    </p>
                </div>
                <a href="{{ route('scanner.dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($events as $event)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->name }}</h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-calendar-event me-2"></i>
                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                        </p>
                        <p class="card-text">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            {{ $event->venue }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-{{ $event->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                            <a href="{{ route('scanner.select-event', ['event' => $event->id]) }}" 
                               class="btn btn-primary">
                                <i class="fas fa-qrcode me-2"></i>Start Scanning
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h5>
                                @if(auth()->user()->hasRole('admin'))
                                    No Active Events
                                @else
                                    No Assigned Events
                                @endif
                            </h5>
                            <p class="mb-0">
                                @if(auth()->user()->hasRole('admin'))
                                    There are no active events available at the moment.
                                @else
                                    You have not been assigned to any events yet. Please contact your administrator.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $events->links() }}
    </div>
</div>
@endsection 