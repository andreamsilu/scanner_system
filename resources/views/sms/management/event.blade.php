@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <x-page-header 
        title="SMS Management - {{ $event->name }}"
        :breadcrumbs="[
            'SMS Management' => route('sms.management.index'),
            $event->name => null
        ]"
    />

    <!-- Event Stats -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Total SMS Sent</h6>
                            <h5 class="card-title mb-0">{{ $event->sms_logs_count ?? 0 }}</h5>
                            <small class="text-muted small">For this event</small>
                        </div>
                        <div class="p-1 rounded-circle bg-info bg-opacity-10 text-info">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Success Rate</h6>
                            <h5 class="card-title mb-0">
                                @php
                                    $smsLogs = $event->sms_logs ?? collect();
                                    $successRate = $smsLogs->count() > 0 
                                        ? round(($smsLogs->where('status', 'delivered')->count() / $smsLogs->count()) * 100, 1)
                                        : 0;
                                @endphp
                                {{ $successRate }}%
                            </h5>
                            <small class="text-muted small">Delivered messages</small>
                        </div>
                        <div class="p-1 rounded-circle bg-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }}">
                            <i class="bi bi-{{ $successRate >= 90 ? 'check-circle' : ($successRate >= 70 ? 'exclamation-circle' : 'x-circle') }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Total Attendees</h6>
                            <h5 class="card-title mb-0">{{ $event->attendees_count ?? 0 }}</h5>
                            <small class="text-muted small">Registered attendees</small>
                        </div>
                        <div class="p-1 rounded-circle bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Last SMS Sent</h6>
                            <h5 class="card-title mb-0">
                                @php
                                    $smsLogs = $event->sms_logs ?? collect();
                                @endphp
                                @if($smsLogs->isNotEmpty())
                                    {{ $smsLogs->first()->created_at->format('M d, g:i A') }}
                                @else
                                    Never
                                @endif
                            </h5>
                            <small class="text-muted small">Most recent message</small>
                        </div>
                        <div class="p-1 rounded-circle bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- Attendees List -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Attendees</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllBtn">
                            <i class="bi bi-check-square me-2"></i>Select All
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
                            <i class="bi bi-send me-2"></i>Send SMS
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="attendeesTable">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </div>
                                    </th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Ticket</th>
                                    <th>Status</th>
                                    <th>Last SMS</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendees as $attendee)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input attendee-checkbox" 
                                                       value="{{ $attendee->id }}" 
                                                       data-phone="{{ $attendee->phone_number }}">
                                            </div>
                                        </td>
                                        <td>{{ $attendee->name }}</td>
                                        <td>{{ $attendee->phone_number }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attendee->ticket->status_color }}">
                                                {{ $attendee->ticket->ticket_number }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($attendee->phone_number)
                                                <span class="badge bg-success">Has Phone</span>
                                            @else
                                                <span class="badge bg-danger">No Phone</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendee->last_sms)
                                                <span class="text-muted small">
                                                    {{ $attendee->last_sms->created_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted small">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary send-single-sms"
                                                        data-attendee-id="{{ $attendee->id }}"
                                                        data-attendee-name="{{ $attendee->name }}"
                                                        data-attendee-phone="{{ $attendee->phone_number }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#sendSingleSmsModal">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                        data-bs-toggle="dropdown">
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewSmsHistoryModal" 
                                                           data-attendee-id="{{ $attendee->id }}">
                                                            <i class="bi bi-clock-history me-2"></i>View SMS History
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Templates & Recent Activity -->
        <div class="col-md-4">
            <!-- Recent SMS Activity -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent SMS Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recent_logs as $log)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $log->recipient_name }}</h6>
                                    <span class="badge bg-{{ $log->status === 'delivered' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </div>
                                <p class="mb-1 small">{{ Str::limit($log->content, 60) }}</p>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                        @empty
                            <div class="list-group-item text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-chat-dots fa-2x mb-2"></i>
                                    <p class="mb-0">No recent SMS activity</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sendSmsForm" action="{{ route('sms.management.event.bulk-send', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send SMS to Selected Attendees</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Status Messages -->
                    <div id="smsStatusContainer">
                        <div class="alert alert-info" id="selectedCountAlert">
                            <i class="bi bi-info-circle me-2"></i>
                            <span id="selectedCount">0</span> attendees selected
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Only attendees with valid phone numbers will receive SMS.
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="smsLoadingState" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mb-0">Sending SMS messages...</p>
                    </div>

                    <!-- Results Container -->
                    <div id="smsResultsContainer" style="display: none;">
                        <div class="alert" id="smsResultAlert">
                            <div class="d-flex align-items-center">
                                <i class="bi me-2" id="smsResultIcon"></i>
                                <div>
                                    <h6 class="alert-heading mb-1" id="smsResultTitle"></h6>
                                    <p class="mb-0" id="smsResultMessage"></p>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm" id="smsResultsTable">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Recipient</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="sendSmsBtn">
                        <i class="bi bi-send me-1"></i> Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View SMS History Modal -->
<div class="modal fade" id="viewSmsHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SMS History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="smsHistoryTable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Content</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Single SMS Modal -->
<div class="modal fade" id="sendSingleSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sendSingleSmsForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send SMS to <span id="singleSmsRecipientName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Status Messages -->
                    <div id="singleSmsStatusContainer">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Sending SMS to: <span id="singleSmsRecipientPhone"></span>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="singleSmsLoadingState" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Sending...</span>
                        </div>
                        <p class="mb-0">Sending SMS...</p>
                    </div>

                    <!-- Results Container -->
                    <div id="singleSmsResultsContainer" style="display: none;">
                        <div class="alert" id="singleSmsResultAlert">
                            <div class="d-flex align-items-center">
                                <i class="bi me-2" id="singleSmsResultIcon"></i>
                                <div>
                                    <h6 class="alert-heading mb-1" id="singleSmsResultTitle"></h6>
                                    <p class="mb-0" id="singleSmsResultMessage"></p>
                                </div>
                            </div>
                        </div>
                        <div id="singleSmsResultDetails" class="mt-3"></div>
                    </div>

                    <input type="hidden" name="attendee_id" id="singleSmsAttendeeId">
                    <input type="hidden" name="ticket_id" id="singleSmsTicketId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="sendSingleSmsBtn">
                        <i class="bi bi-send me-1"></i> Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .dropdown-menu {
        min-width: 200px;
    }
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    .list-group-item:first-child {
        border-top: none;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    const attendeesTable = $('#attendeesTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 10,
        language: {
            search: "",
            searchPlaceholder: "Search attendees..."
        },
        columnDefs: [
            { orderable: false, targets: [0, -1] }
        ]
    });

    // Select All functionality
    let allSelected = false;
    $('#selectAll').on('change', function() {
        allSelected = $(this).prop('checked');
        $('.attendee-checkbox').prop('checked', allSelected);
        updateSelectedCount();
    });

    $('.attendee-checkbox').on('change', function() {
        updateSelectedCount();
        const totalCheckboxes = $('.attendee-checkbox').length;
        const checkedCheckboxes = $('.attendee-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    $('#selectAllBtn').on('click', function() {
        allSelected = !allSelected;
        $('#selectAll').prop('checked', allSelected).trigger('change');
    });

    function updateSelectedCount() {
        const count = $('.attendee-checkbox:checked').length;
        $('#selectedCount').text(count);
    }

    function showValidationResult(success, message, details = null) {
        const container = $('#smsResultsContainer');
        const alert = $('#smsResultAlert');
        const icon = $('#smsResultIcon');
        const title = $('#smsResultTitle');
        const messageEl = $('#smsResultMessage');
        const resultsTable = $('#smsResultsTable tbody');
        
        // Clear previous results
        resultsTable.empty();
        
        // Set alert style and icon
        alert.removeClass('alert-success alert-danger alert-warning')
            .addClass(success ? 'alert-success' : 'alert-danger');
        
        icon.removeClass('bi-check-circle bi-x-circle bi-exclamation-triangle')
            .addClass(success ? 'bi-check-circle' : 'bi-x-circle');
        
        // Set content with enhanced messaging
        title.text(success ? 'Validation Successful' : 'Validation Failed');
        messageEl.html(success 
            ? `<strong>✓ ${message}</strong><br><small class="text-muted">All selected attendees have been validated successfully.</small>`
            : `<strong>✗ ${message}</strong><br><small class="text-muted">Please review the details below and try again.</small>`);
        
        // Add detailed results if available
        if (details && Array.isArray(details)) {
            details.forEach(detail => {
                resultsTable.append(`
                    <tr>
                        <td>
                            <span class="badge bg-${detail.status === 'success' ? 'success' : 'danger'}">
                                ${detail.status === 'success' ? '✓ Valid' : '✗ Invalid'}
                            </span>
                        </td>
                        <td>${detail.recipient}</td>
                        <td>
                            <small class="text-${detail.status === 'success' ? 'success' : 'danger'}">
                                ${detail.message}
                            </small>
                        </td>
                    </tr>
                `);
            });
        }
        
        // Show results container with animation
        container.hide().fadeIn(300);
    }

    // Handle bulk validation with enhanced feedback
    $('#sendSmsForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#sendSmsBtn');
        const selectedAttendees = $('.attendee-checkbox:checked').map(function() {
            return {
                id: $(this).val(),
                name: $(this).closest('tr').find('td:eq(1)').text(),
                phone: $(this).data('phone')
            };
        }).get();

        if (selectedAttendees.length === 0) {
            showValidationResult(false, 'No attendees selected', [{
                status: 'error',
                recipient: 'System',
                message: 'Please select at least one attendee to validate.'
            }]);
            return;
        }

        // Show loading state with enhanced feedback
        $('#smsStatusContainer').hide();
        $('#smsLoadingState').show().html(`
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Validating...</span>
            </div>
            <p class="mb-0">Validating ${selectedAttendees.length} attendee${selectedAttendees.length > 1 ? 's' : ''}...</p>
            <small class="text-muted">Checking token status</small>
        `);
        $('#smsResultsContainer').hide();
        submitBtn.prop('disabled', true);

        // Validate tokens
        $.ajax({
            url: '/scanner/scan/validate',
            method: 'POST',
            data: {
                token: selectedAttendees.map(a => a.id).join(','),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    const details = selectedAttendees.map(attendee => ({
                        status: 'success',
                        recipient: `${attendee.name}`,
                        message: 'Token is valid and active',
                        statusClass: 'success'
                    }));
                    showValidationResult(true, 'All tokens validated successfully', details);
                    
                    // Update status in the table
                    selectedAttendees.forEach(attendee => {
                        const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                        const statusCell = row.find('td:eq(4)');
                        statusCell.html('<span class="badge bg-success">Valid Token</span>');
                    });
                } else {
                    let status = 'invalid';
                    let statusClass = 'warning';
                    let message = 'Token validation failed';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status) {
                            status = response.status;
                            statusClass = {
                                'success': 'success',
                                'warning': 'warning',
                                'error': 'warning',
                                'duplicate': 'warning',
                                'invalid': 'warning',
                                'expired': 'warning',
                                'used': 'warning'
                            }[status] || 'warning';
                            message = response.message || 'Token is not valid';
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        status = 'invalid';
                        message = 'Token is not valid';
                    }

                    const details = selectedAttendees.map(attendee => ({
                        status: status,
                        recipient: attendee.name,
                        message: message,
                        statusClass: statusClass
                    }));
                    showValidationResult(false, message, details);
                    
                    // Update status in the table
                    selectedAttendees.forEach(attendee => {
                        const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                        const statusCell = row.find('td:eq(4)');
                        statusCell.html(`<span class="badge bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
                    });
                }
            },
            error: function(xhr) {
                let status = 'invalid';
                let statusClass = 'warning';
                let message = 'Token validation failed';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status) {
                        status = response.status;
                        statusClass = {
                            'success': 'success',
                            'warning': 'warning',
                            'error': 'warning',
                            'duplicate': 'warning',
                            'invalid': 'warning',
                            'expired': 'warning',
                            'used': 'warning'
                        }[status] || 'warning';
                        message = response.message || 'Token is not valid';
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    status = 'invalid';
                    message = 'Token is not valid';
                }

                const details = selectedAttendees.map(attendee => ({
                    status: status,
                    recipient: attendee.name,
                    message: message,
                    statusClass: statusClass
                }));
                showValidationResult(false, message, details);
                
                // Update status in the table
                selectedAttendees.forEach(attendee => {
                    const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    statusCell.html(`<span class="badge bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
                });
            },
            complete: function() {
                $('#smsLoadingState').hide();
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Handle single validation with enhanced feedback
    $('#sendSingleSmsForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#sendSingleSmsBtn');
        const attendeeName = $('#singleSmsRecipientName').text();
        const attendeeId = $('#singleSmsAttendeeId').val();
        
        // Show loading state with enhanced feedback
        $('#singleSmsStatusContainer').hide();
        $('#singleSmsLoadingState').show().html(`
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Validating...</span>
            </div>
            <p class="mb-0">Validating token...</p>
            <small class="text-muted">Checking token status</small>
        `);
        $('#singleSmsResultsContainer').hide();
        submitBtn.prop('disabled', true);

        // Validate token
        $.ajax({
            url: '/scanner/scan/validate',
            method: 'POST',
            data: {
                token: attendeeId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                const alert = $('#singleSmsResultAlert');
                const icon = $('#singleSmsResultIcon');
                const title = $('#singleSmsResultTitle');
                const message = $('#singleSmsResultMessage');
                const details = $('#singleSmsResultDetails');
                
                if (response.success) {
                    const statusClass = 'success';
                    alert.removeClass('alert-danger alert-warning').addClass('alert-success');
                    icon.removeClass('bi-x-circle bi-exclamation-circle').addClass('bi-check-circle');
                    title.text('Token Valid');
                    message.html(`
                        <strong>✓ Token is valid and active</strong><br>
                        <small class="text-muted">The token has been validated successfully.</small>
                    `);
                    details.html(`
                        <div class="alert alert-light border">
                            <h6 class="alert-heading mb-2">Validation Details</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Attendee</dt>
                                <dd class="col-sm-8">${attendeeName}</dd>
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8"><span class="badge bg-success">Valid</span></dd>
                                <dt class="col-sm-4">Validated At</dt>
                                <dd class="col-sm-8">${new Date().toLocaleString()}</dd>
                            </dl>
                        </div>
                    `);
                    
                    // Update status in the table
                    const row = $(`.attendee-checkbox[value="${attendeeId}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    statusCell.html('<span class="badge bg-success">Valid Token</span>');
                } else {
                    let status = 'invalid';
                    let statusClass = 'warning';
                    let message = 'Token validation failed';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status) {
                            status = response.status;
                            statusClass = {
                                'success': 'success',
                                'warning': 'warning',
                                'error': 'warning',
                                'duplicate': 'warning',
                                'invalid': 'warning',
                                'expired': 'warning',
                                'used': 'warning'
                            }[status] || 'warning';
                            message = response.message || 'Token is not valid';
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        status = 'invalid';
                        message = 'Token is not valid';
                    }

                    const details = selectedAttendees.map(attendee => ({
                        status: status,
                        recipient: attendee.name,
                        message: message,
                        statusClass: statusClass
                    }));
                    showValidationResult(false, message, details);
                    
                    // Update status in the table
                    const row = $(`.attendee-checkbox[value="${attendeeId}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    statusCell.html(`<span class="badge bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
                }
                
                $('#singleSmsResultsContainer').fadeIn(300);
            },
            error: function(xhr) {
                const alert = $('#singleSmsResultAlert');
                const icon = $('#singleSmsResultIcon');
                const title = $('#singleSmsResultTitle');
                const message = $('#singleSmsResultMessage');
                const details = $('#singleSmsResultDetails');
                
                let status = 'invalid';
                let statusClass = 'warning';
                let message = 'Token validation failed';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status) {
                        status = response.status;
                        statusClass = {
                            'success': 'success',
                            'warning': 'warning',
                            'error': 'warning',
                            'duplicate': 'warning',
                            'invalid': 'warning',
                            'expired': 'warning',
                            'used': 'warning'
                        }[status] || 'warning';
                        message = response.message || 'Token is not valid';
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    status = 'invalid';
                    message = 'Token is not valid';
                }
                
                alert.removeClass('alert-success alert-danger').addClass(`alert-${statusClass}`);
                icon.removeClass('bi-check-circle').addClass('bi-exclamation-circle');
                title.text('Token Status');
                message.html(`
                    <strong>${message}</strong><br>
                    <small class="text-muted">Please check the validation details below.</small>
                `);
                details.html(`
                    <div class="alert alert-light border">
                        <h6 class="alert-heading mb-2">Validation Details</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Attendee</dt>
                            <dd class="col-sm-8">${attendeeName}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8"><span class="badge bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></dd>
                            <dt class="col-sm-4">Last Check</dt>
                            <dd class="col-sm-8">${new Date().toLocaleString()}</dd>
                        </dl>
                    </div>
                `);
                
                // Update status in the table
                const row = $(`.attendee-checkbox[value="${attendeeId}"]`).closest('tr');
                const statusCell = row.find('td:eq(4)');
                statusCell.html(`<span class="badge bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`);
                
                $('#singleSmsResultsContainer').fadeIn(300);
            },
            complete: function() {
                $('#singleSmsLoadingState').hide();
                submitBtn.prop('disabled', false);
            }
        });
    });

    // View SMS History
    $('#viewSmsHistoryModal').on('show.bs.modal', function(e) {
        const attendeeId = $(e.relatedTarget).data('attendee-id');
        const modal = $(this);
        
        // Clear existing data
        modal.find('#smsHistoryTable tbody').empty();
        
        // Fetch SMS history
        $.get(`/api/sms/attendee/${attendeeId}/history`, function(response) {
            if (response.data.length > 0) {
                response.data.forEach(log => {
                    modal.find('#smsHistoryTable tbody').append(`
                        <tr>
                            <td>${log.created_at}</td>
                            <td>${log.type}</td>
                            <td>${log.content}</td>
                            <td>
                                <span class="badge bg-${log.status === 'delivered' ? 'success' : (log.status === 'failed' ? 'danger' : 'warning')}">
                                    ${log.status}
                                </span>
                            </td>
                        </tr>
                    `);
                });
            } else {
                modal.find('#smsHistoryTable tbody').append(`
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-chat-dots fa-2x mb-2"></i>
                                <p class="mb-0">No SMS history found</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
        });
    });
});
</script>
@endpush
@endsection 