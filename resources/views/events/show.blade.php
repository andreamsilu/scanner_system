<x-app-layout>
    <x-page-header 
        :title="$event->name"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-pencil me-2"></i>Edit Event
            </a>
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Events
            </a>
        </x-slot>
    </x-page-header>

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
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Public registration</h5>
                    <p class="text-muted">Share a form where guests can register without an account and receive a single-entry QR ticket. Registration closes when the event ends or reaches capacity.</p>
                    @if($event->public_registration_token)
                        <span class="badge bg-{{ $event->public_registration_enabled ? 'success' : 'secondary' }} mb-3">{{ $event->public_registration_enabled ? 'Enabled' : 'Disabled' }}</span>
                        <label class="form-label d-block" for="public-registration-link">Registration link</label>
                        <div class="input-group mb-3" x-data="{ copied: false }">
                            <input id="public-registration-link" class="form-control" type="text" readonly value="{{ route('public-registration.show', $event->public_registration_token) }}" x-ref="link" x-on:click="$el.select()">
                            <button type="button" class="btn btn-outline-primary" x-on:click="(async () => { try { if (window.isSecureContext &amp;&amp; navigator.clipboard) { await navigator.clipboard.writeText($refs.link.value); copied = true; } else { $refs.link.select(); copied = document.execCommand('copy'); } } catch (e) { $refs.link.select(); } })()" x-text="copied ? 'Copied!' : 'Copy link'">Copy link</button>
                            @if($event->public_registration_enabled)<a href="{{ route('public-registration.show', $event->public_registration_token) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Open form</a>@endif
                        </div>
                    @endif
                    <form method="POST" action="{{ route('events.public-registration', $event) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        @if(!$event->public_registration_token)
                            <button class="btn btn-primary" name="action" value="generate">Generate registration link</button>
                        @else
                            <button class="btn btn-{{ $event->public_registration_enabled ? 'outline-secondary' : 'primary' }}" name="action" value="{{ $event->public_registration_enabled ? 'disable' : 'enable' }}">{{ $event->public_registration_enabled ? 'Disable registration' : 'Enable registration' }}</button>
                            <button class="btn btn-outline-primary" name="action" value="regenerate" onclick="return confirm('Replace the registration link? The previous link will stop working.');">Generate new link</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
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

        <!-- RSVP Information Card -->
        @if($event->rsvp_enabled)
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-telephone me-2"></i>RSVP Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($event->rsvp_message)
                        <p class="mb-3"><strong>Message:</strong> {{ $event->rsvp_message }}</p>
                    @endif
                    
                    <div class="row g-3">
                        @if($event->rsvp_option_1_label && $event->rsvp_option_1_phone)
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded bg-light">
                                <i class="bi bi-check-circle text-success me-3 fs-4"></i>
                                <div>
                                    <strong>{{ $event->rsvp_option_1_label }}</strong><br>
                                    <small class="text-muted">{{ $event->rsvp_option_1_phone }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($event->rsvp_option_2_label && $event->rsvp_option_2_phone)
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded bg-light">
                                <i class="bi bi-x-circle text-danger me-3 fs-4"></i>
                                <div>
                                    <strong>{{ $event->rsvp_option_2_label }}</strong><br>
                                    <small class="text-muted">{{ $event->rsvp_option_2_phone }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Scanner Assignments Card - Full Width Row -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-upc-scan me-2"></i>Scanner Assignments
                        </h5>
                        @if(auth()->user()->hasRole('admin'))
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignScannerModal">
                                <i class="bi bi-person-plus me-2"></i>Assign Scanner
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($event->scanners->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-upc-scan text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No scanners assigned to this event yet.</p>
                            @if(auth()->user()->hasRole('admin'))
                                <button type="button" class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#assignScannerModal">
                                    <i class="bi bi-person-plus me-2"></i>Assign Scanner
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Scanner</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Assigned At</th>
                                        <th>Assigned By</th>
                                        @if(auth()->user()->hasRole('admin'))
                                            <th class="text-end">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($event->scanners as $scanner)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <span class="avatar-title rounded-circle bg-primary bg-opacity-10 text-primary">
                                                            {{ substr($scanner->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $scanner->name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $scanner->email }}</td>
                                            <td>
                                                @if($scanner->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($scanner->pivot->assigned_at)
                                                    {{ is_string($scanner->pivot->assigned_at) 
                                                        ? \Carbon\Carbon::parse($scanner->pivot->assigned_at)->format('M d, Y h:i A')
                                                        : $scanner->pivot->assigned_at->format('M d, Y h:i A') 
                                                    }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($scanner->pivot->assigned_by)
                                                    @php
                                                        $assignedBy = \App\Models\User::find($scanner->pivot->assigned_by);
                                                    @endphp
                                                    {{ $assignedBy ? $assignedBy->name : 'System' }}
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                            @if(auth()->user()->hasRole('admin'))
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <button type="button" 
                                                            class="btn btn-light btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editScannerModal{{ $scanner->id }}"
                                                            title="Edit Assignment">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form action="{{ route('events.scanners.remove', [$event, $scanner]) }}" 
                                                            method="POST" 
                                                            class="d-inline"
                                                            onsubmit="return confirm('Are you sure you want to remove this scanner?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-light btn-sm text-danger" title="Remove Scanner">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
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
                            <a href="{{ route('events.guests.index', ['event' => $event, 'add' => 1]) }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-person-plus me-2"></i>Add Guest
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('events.guests.index', $event) }}" class="btn btn-outline-success w-100">
                                <i class="bi bi-people me-2"></i>Guests &amp; Tickets
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('events.attendees.bulk-upload', $event) }}" class="btn btn-outline-info w-100">
                                <i class="bi bi-upload me-2"></i>Import Guests
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('events.scanners.assign', $event) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignScannerModalLabel">
                            <i class="bi bi-person-plus me-2"></i>Assign Scanner
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $availableScanners = $scannerUsers->whereNotIn('id', $event->scanners->pluck('id'));
                        @endphp

                        @if($availableScanners->isEmpty())
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No available scanners to assign. All scanner users are already assigned to this event.
                            </div>
                        @else
                        <div class="mb-3">
                            <label for="scanner_id" class="form-label">Select Scanner</label>
                            <select class="form-select" id="scanner_id" name="scanner_id" required>
                                <option value="">Choose a scanner...</option>
                                    @foreach($availableScanners as $scanner)
                                        <option value="{{ $scanner->id }}" 
                                                data-email="{{ $scanner->email }}"
                                                data-status="{{ $scanner->is_active ? 'Active' : 'Inactive' }}"
                                                data-last-active="{{ $scanner->last_login_at?->diffForHumans() ?? 'Never' }}">
                                            {{ $scanner->name }}
                                        </option>
                                @endforeach
                            </select>
                        </div>

                            <div id="scannerDetails" class="d-none">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Scanner Details</h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-3">Email:</dt>
                                            <dd class="col-sm-9" id="scannerEmail"></dd>
                                            
                                            <dt class="col-sm-3">Status:</dt>
                                            <dd class="col-sm-9" id="scannerStatus"></dd>
                                            
                                            <dt class="col-sm-3">Last Active:</dt>
                                            <dd class="col-sm-9" id="scannerLastActive"></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        @if(!$availableScanners->isEmpty())
                        <button type="submit" class="btn btn-primary">Assign Scanner</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Scanner Modals -->
    @foreach($event->scanners as $scanner)
    <div class="modal fade" id="editScannerModal{{ $scanner->id }}" tabindex="-1" aria-labelledby="editScannerModalLabel{{ $scanner->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('events.scanners.update', [$event, $scanner]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editScannerModalLabel{{ $scanner->id }}">
                            <i class="bi bi-person-gear me-2"></i>Change Scanner
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Current Scanner: <strong>{{ $scanner->name }}</strong>
                        </div>
                        
                        @php
                            $availableScanners = $scannerUsers->whereNotIn('id', $event->scanners->pluck('id'));
                        @endphp
                        
                        @if($availableScanners->isEmpty())
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No available scanners to replace the current one.
                            </div>
                        @else
                            <div class="mb-3">
                                <label for="new_scanner_id{{ $scanner->id }}" class="form-label">Select New Scanner</label>
                                <select class="form-select" id="new_scanner_id{{ $scanner->id }}" name="new_scanner_id" required>
                                    <option value="">Choose a scanner...</option>
                                    @foreach($availableScanners as $newScanner)
                                        <option value="{{ $newScanner->id }}"
                                                data-email="{{ $newScanner->email }}"
                                                data-status="{{ $newScanner->is_active ? 'Active' : 'Inactive' }}"
                                                data-last-active="{{ $newScanner->last_login_at?->diffForHumans() ?? 'Never' }}">
                                            {{ $newScanner->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="newScannerDetails{{ $scanner->id }}" class="d-none">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">New Scanner Details</h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-3">Email:</dt>
                                            <dd class="col-sm-9" id="newScannerEmail{{ $scanner->id }}"></dd>
                                            
                                            <dt class="col-sm-3">Status:</dt>
                                            <dd class="col-sm-9" id="newScannerStatus{{ $scanner->id }}"></dd>
                                            
                                            <dt class="col-sm-3">Last Active:</dt>
                                            <dd class="col-sm-9" id="newScannerLastActive{{ $scanner->id }}"></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        @if(!$availableScanners->isEmpty())
                            <button type="submit" class="btn btn-primary">Update Scanner</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // Handle scanner selection in assign modal
        const scannerSelect = document.getElementById('scanner_id');
        const scannerDetails = document.getElementById('scannerDetails');
        const scannerEmail = document.getElementById('scannerEmail');
        const scannerStatus = document.getElementById('scannerStatus');
        const scannerLastActive = document.getElementById('scannerLastActive');

        if (scannerSelect) {
            scannerSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value) {
                    scannerEmail.textContent = selectedOption.dataset.email;
                    scannerStatus.innerHTML = `<span class="badge bg-${selectedOption.dataset.status === 'Active' ? 'success' : 'danger'}">${selectedOption.dataset.status}</span>`;
                    scannerLastActive.textContent = selectedOption.dataset.lastActive;
                    scannerDetails.classList.remove('d-none');
                } else {
                    scannerDetails.classList.add('d-none');
                }
            });
        }

        // Handle scanner selection in edit modals
        @foreach($event->scanners as $scanner)
        const newScannerSelect{{ $scanner->id }} = document.getElementById('new_scanner_id{{ $scanner->id }}');
        const newScannerDetails{{ $scanner->id }} = document.getElementById('newScannerDetails{{ $scanner->id }}');
        const newScannerEmail{{ $scanner->id }} = document.getElementById('newScannerEmail{{ $scanner->id }}');
        const newScannerStatus{{ $scanner->id }} = document.getElementById('newScannerStatus{{ $scanner->id }}');
        const newScannerLastActive{{ $scanner->id }} = document.getElementById('newScannerLastActive{{ $scanner->id }}');

        if (newScannerSelect{{ $scanner->id }}) {
            newScannerSelect{{ $scanner->id }}.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value) {
                    newScannerEmail{{ $scanner->id }}.textContent = selectedOption.dataset.email;
                    newScannerStatus{{ $scanner->id }}.innerHTML = `<span class="badge bg-${selectedOption.dataset.status === 'Active' ? 'success' : 'danger'}">${selectedOption.dataset.status}</span>`;
                    newScannerLastActive{{ $scanner->id }}.textContent = selectedOption.dataset.lastActive;
                    newScannerDetails{{ $scanner->id }}.classList.remove('d-none');
                } else {
                    newScannerDetails{{ $scanner->id }}.classList.add('d-none');
                }
            });
        }
        @endforeach
    });
    </script>
    @endpush

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
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-title {
            font-size: 0.875rem;
            font-weight: 500;
        }
        .table > :not(caption) > * > * {
            padding: 1rem;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
        .btn-group .btn i {
            font-size: 0.875rem;
        }
    </style>
    @endpush
</x-app-layout>
