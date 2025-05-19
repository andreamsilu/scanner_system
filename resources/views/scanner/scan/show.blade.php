@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h3 mb-0">Scan Details</h2>
                <div>
                    <a href="{{ route('scanner.scan.index', ['event' => $event->id]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Scanning
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Scan Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Scan ID:</div>
                        <div class="col-md-8">{{ $scan->id }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Event:</div>
                        <div class="col-md-8">{{ $event->name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Attendee:</div>
                        <div class="col-md-8">{{ $scan->ticket->attendee->name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Ticket Type:</div>
                        <div class="col-md-8">{{ $scan->ticket->type }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Scanned By:</div>
                        <div class="col-md-8">{{ $scan->scanner->name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Scan Time:</div>
                        <div class="col-md-8">{{ $scan->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                {{ ucfirst($scan->status) }}
                            </span>
                        </div>
                    </div>

                    @if($scan->notes)
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Notes:</div>
                            <div class="col-md-8">{{ $scan->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Quick Actions</h5>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('scanner.scan.index', ['event' => $event->id]) }}" 
                           class="btn btn-primary">
                            <i class="bi bi-upc-scan me-2"></i>Continue Scanning
                        </a>
                        
                        <a href="{{ route('scanner.events.index') }}" 
                           class="btn btn-outline-secondary">
                            <i class="bi bi-calendar-event me-2"></i>Back to Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 