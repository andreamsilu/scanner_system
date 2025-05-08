@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Attendees Navigation Sidebar -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-users me-2"></i>Event Attendees
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Event Details -->
                        <a href="{{ route('events.show', $event) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Event Details
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>All users
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- All Cards -->
                        <a href="{{ route('event-cards.index', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-id-card me-2 text-primary"></i>
                                All Cards
                                <span class="badge bg-primary rounded-pill ms-2">{{ $event->attendees_count ?? 0 }}</span>
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Create New Card -->
                        <a href="{{ route('event-cards.create', ['event' => $event->id]) }}" class="list-group-item list-group-item-action active">
                            <div>
                                <i class="fas fa-plus-circle me-2"></i>
                                Create New Card
                                <small class="d-block text-white mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                        </a>

                        <!-- Import Attendees -->
                        <a href="{{ route('event-cards.import', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-import me-2 text-primary"></i>
                                Import Attendees
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers only
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Export Attendees -->
                        <a href="{{ route('event-cards.export', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-export me-2 text-primary"></i>
                                Export Attendees
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Manage Access -->
                        <a href="{{ route('event-cards.access', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-lock me-2 text-primary"></i>
                                Manage Access
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers only
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Check-in Station -->
                        <a href="{{ route('event-cards.checkin', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-qrcode me-2 text-primary"></i>
                                Check-in Station
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Check-in staff & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Quick Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total Attendees</span>
                        <span class="badge bg-primary">{{ $event->attendees_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Remaining Capacity</span>
                        <span class="badge bg-success">{{ $event->remaining_capacity }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Event Status</span>
                        <span class="badge bg-{{ $event->status_badge }}">{{ ucfirst($event->status) }}</span>
                    </div>
                </div>
            </div>

            <!-- Access Control Info -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-user-shield me-2"></i>Access Levels
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-2">Event Organizers</h6>
                        <p class="text-muted small mb-0">Full access to all features including attendee management, imports, and access control.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-2">Event Admins</h6>
                        <p class="text-muted small mb-0">Can view and manage attendees, create cards, and handle check-ins.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-2">Check-in Staff</h6>
                        <p class="text-muted small mb-0">Limited to check-in station and basic attendee information.</p>
                    </div>
                    <div>
                        <h6 class="mb-2">Regular Users</h6>
                        <p class="text-muted small mb-0">Can only view event details and their own information.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Attendees List -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-users me-2"></i>Event Attendees
                        </h6>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="searchAttendees" 
                                       placeholder="Search attendees...">
                            </div>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#filterCollapse">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
                <div class="collapse" id="filterCollapse">
                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Ticket Type</label>
                                <select class="form-select" id="filterTicketType">
                                    <option value="">All Types</option>
                                    <option value="regular">Regular</option>
                                    <option value="vip">VIP</option>
                                    <option value="speaker">Speaker</option>
                                    <option value="sponsor">Sponsor</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Company</label>
                                <input type="text" class="form-control" id="filterCompany" 
                                       placeholder="Filter by company">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="checked_in">Checked In</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Name</th>
                                <th class="border-0">Email</th>
                                <th class="border-0">Company</th>
                                <th class="border-0">Ticket Type</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($event->attendees as $attendee)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <span class="avatar-title rounded-circle bg-primary text-white">
                                                {{ substr($attendee->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $attendee->name }}</h6>
                                            <small class="text-muted">{{ $attendee->position }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $attendee->email }}</td>
                                <td>{{ $attendee->company }}</td>
                                <td>
                                    <span class="badge bg-{{ $attendee->ticket_type_badge }}">
                                        {{ ucfirst($attendee->ticket_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $attendee->status_badge }}">
                                        {{ ucfirst($attendee->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-light" 
                                                data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light" 
                                                data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light text-danger" 
                                                data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-2x mb-3"></i>
                                        <p class="mb-0">No attendees found for this event.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($event->attendees->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    {{ $event->attendees->links() }}
                </div>
                @endif
            </div>

            <!-- Create Card Form -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-id-card me-2"></i>Create Event Card
                        </h5>
                        <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Event
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('event-cards.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="event_id" value="{{ $event->id }}">

                        <div class="event-info bg-light rounded p-4 mb-4">
                            <h6 class="text-muted mb-3">Event Information</h6>
                            <div class="d-flex align-items-center">
                                @if($event->banner)
                                    <img src="{{ $event->banner_url }}" alt="{{ $event->name }}" 
                                         class="rounded me-3" style="width: 64px; height: 64px; object-fit: cover;">
                                @endif
                                <div>
                                    <h4 class="mb-1">{{ $event->name }}</h4>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-2"></i>
                                        {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        {{ $event->venue }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card-format mb-4">
                            <h6 class="text-muted mb-3">Card Format</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="card_format" 
                                               id="formatStandard" value="standard" checked>
                                        <label class="form-check-label" for="formatStandard">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-id-card text-primary me-2"></i>
                                                <div>
                                                    <strong>Standard Format</strong>
                                                    <p class="text-muted small mb-0">Basic card with QR code and essential information</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="card_format" 
                                               id="formatPremium" value="premium">
                                        <label class="form-check-label" for="formatPremium">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-id-card-alt text-primary me-2"></i>
                                                <div>
                                                    <strong>Premium Format</strong>
                                                    <p class="text-muted small mb-0">Enhanced card with additional details and branding</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="attendee-details">
                            <h6 class="text-muted mb-3">Attendee Information</h6>
                            
                            <div class="mb-4">
                                <label for="name" class="form-label fw-medium">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                        id="name" name="name" value="{{ old('name') }}" 
                                        placeholder="Enter attendee's full name" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                        id="email" name="email" value="{{ old('email') }}" 
                                        placeholder="Enter attendee's email address" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="phone" class="form-label fw-medium">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-phone text-muted"></i>
                                    </span>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                        id="phone" name="phone" value="{{ old('phone') }}" 
                                        placeholder="Enter attendee's phone number">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="company" class="form-label fw-medium">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-building text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('company') is-invalid @enderror" 
                                        id="company" name="company" value="{{ old('company') }}" 
                                        placeholder="Enter attendee's company name">
                                    @error('company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="position" class="form-label fw-medium">Position</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-briefcase text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                        id="position" name="position" value="{{ old('position') }}" 
                                        placeholder="Enter attendee's position">
                                    @error('position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="ticket_type" class="form-label fw-medium">Ticket Type</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-ticket-alt text-muted"></i>
                                    </span>
                                    <select class="form-select @error('ticket_type') is-invalid @enderror" 
                                            id="ticket_type" name="ticket_type" required>
                                        <option value="">Select ticket type</option>
                                        <option value="regular" {{ old('ticket_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                        <option value="vip" {{ old('ticket_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                                        <option value="speaker" {{ old('ticket_type') == 'speaker' ? 'selected' : '' }}>Speaker</option>
                                        <option value="sponsor" {{ old('ticket_type') == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                                    </select>
                                    @error('ticket_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Event Card
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border-radius: 15px;
        overflow: hidden;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }
    .input-group-text {
        border-radius: 8px 0 0 8px;
        border: 1px solid #dee2e6;
    }
    .input-group .form-control, .input-group .form-select {
        border-radius: 0 8px 8px 0;
    }
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }
    .btn-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca);
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(45deg, #0a58ca, #084298);
        transform: translateY(-1px);
    }
    .btn-outline-secondary:hover {
        transform: translateY(-1px);
    }
    .invalid-feedback {
        font-size: 0.875rem;
    }
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    .form-check-label {
        cursor: pointer;
    }
    .form-check {
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .form-check:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .form-check-input:checked + .form-check-label .form-check {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .list-group-item {
        border: none;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
    .list-group-item.active {
        background: linear-gradient(45deg, #0d6efd, #0a58ca);
        border: none;
    }
    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }
    .table > :not(caption) > * > * {
        padding: 1rem;
    }
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-group .btn {
        padding: 0.5rem;
    }
    .btn-group .btn:hover {
        background-color: #f8f9fa;
    }
    .btn-group .btn.text-danger:hover {
        background-color: #fee2e2;
    }
    .list-group-item small {
        font-size: 0.75rem;
        opacity: 0.8;
    }
    .list-group-item.active small {
        opacity: 1;
    }
    .card-header h6 {
        font-size: 0.875rem;
        font-weight: 600;
    }
    .access-levels h6 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
    }
    .access-levels p {
        font-size: 0.75rem;
        line-height: 1.4;
    }
</style>
@endpush

@push('scripts')
<script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Phone number formatting
    document.getElementById('phone').addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });

    // Card format selection
    document.querySelectorAll('input[name="card_format"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Add any additional logic for card format changes here
            console.log('Selected card format:', this.value);
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Search functionality
    document.getElementById('searchAttendees').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Filter functionality
    document.querySelectorAll('#filterCollapse select, #filterCollapse input').forEach(function(element) {
        element.addEventListener('change', function() {
            const ticketType = document.getElementById('filterTicketType').value.toLowerCase();
            const company = document.getElementById('filterCompany').value.toLowerCase();
            const status = document.getElementById('filterStatus').value.toLowerCase();
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const rowTicketType = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const rowCompany = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const rowStatus = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                
                const matchesTicketType = !ticketType || rowTicketType.includes(ticketType);
                const matchesCompany = !company || rowCompany.includes(company);
                const matchesStatus = !status || rowStatus.includes(status);
                
                row.style.display = matchesTicketType && matchesCompany && matchesStatus ? '' : 'none';
            });
        });
    });
</script>
@endpush
@endsection 