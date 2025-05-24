<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">{{ $event->name }}</h2>
            <div>
                <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit Event
                </a>
                <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Events
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Event Details Card - Full Width Row -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Event Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Event Name</h6>
                                <p class="mb-0 fs-5">{{ $event->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Venue</h6>
                                <p class="mb-0">
                                    <i class="bi bi-building me-2"></i>{{ $event->venue }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Location</h6>
                                <p class="mb-0">
                                    <i class="bi bi-geo-alt me-2"></i>{{ $event->location }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Status</h6>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Date & Time</h6>
                                <p class="mb-0">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    {{ $event->start_date->format('M d, Y H:i') }} - 
                                    {{ $event->end_date->format('M d, Y H:i') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Capacity</h6>
                                <p class="mb-0">
                                    <i class="bi bi-people me-2"></i>
                                    {{ number_format($event->capacity) }} attendees
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Card - Full Width Row -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Total Attendees</h6>
                                    <h3 class="mb-0">{{ $event->attendees->count() }}</h3>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="bi bi-people-fill text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Checked-in</h6>
                                    <h3 class="mb-0">{{ $event->attendees->where('registration_status', 'checked_in')->count() }}</h3>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="bi bi-person-check-fill text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Unchecked</h6>
                                    <h3 class="mb-0">{{ $event->attendees->where('registration_status', 'registered')->count() }}</h3>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="bi bi-person-dash-fill text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Total Tickets</h6>
                                    <h3 class="mb-0">{{ $event->tickets->count() }}</h3>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="bi bi-ticket-perforated-fill text-info"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Available Capacity</h6>
                                    <h3 class="mb-0">{{ $event->remaining_capacity }}</h3>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="bi bi-person-plus-fill text-info"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h6 class="text-muted mb-1">Assigned Scanners</h6>
                                    <h3 class="mb-0">{{ $event->scanners->count() }}</h3>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="bi bi-upc-scan text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Progress bar for check-in status -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-2">Check-in Progress</h6>
                        <div class="progress" style="height: 8px;">
                            @php
                                $totalAttendees = $event->attendees->count();
                                $checkedInAttendees = $event->attendees->where('registration_status', 'checked_in')->count();
                                $checkInPercentage = $totalAttendees > 0 ? ($checkedInAttendees / $totalAttendees) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: {{ $checkInPercentage }}%"
                                 aria-valuenow="{{ $checkInPercentage }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            {{ $checkedInAttendees }} of {{ $totalAttendees }} attendees checked in
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card - Full Width Row -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-person-plus me-2"></i>Add Attendee
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('events.tickets', $event) }}" class="btn btn-outline-success w-100">
                                <i class="bi bi-ticket-perforated me-2"></i>Manage Tickets
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-info w-100">
                                <i class="bi bi-people me-2"></i>View Attendees
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#assignScannerModal">
                                <i class="bi bi-upc-scan me-2"></i>Assign Scanner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Scanner Modal -->
    <div class="modal fade" id="assignScannerModal" tabindex="-1" aria-labelledby="assignScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignScannerModalLabel">Assign Scanner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('events.scanners.assign', $event) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="scanner_id" class="form-label">Select Scanner</label>
                            <select class="form-select" id="scanner_id" name="scanner_id" required>
                                <option value="">Choose a scanner...</option>
                                @foreach($scannerUsers as $scanner)
                                    <option value="{{ $scanner->id }}">{{ $scanner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Scanner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card {
            border: none;
            border-radius: 0.5rem;
        }
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 1rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .badge {
            padding: 0.5em 1em;
            font-size: 0.875rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        .btn i {
            font-size: 1rem;
        }
        .text-muted {
            font-size: 0.875rem;
        }
        .fs-5 {
            font-weight: 500;
        }
        .bg-opacity-10 {
            --bs-bg-opacity: 0.1;
        }
        .bg-light {
            background-color: #f8f9fa !important;
        }
        .stat-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon i {
            font-size: 1.25rem;
        }
        .stat-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }
        .stat-content h6 {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
    </style>
    @endpush
</x-app-layout> 