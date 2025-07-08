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
                        <button type="button" class="btn btn-success btn-sm" id="sendInvitationSmsBtn">
                            <i class="bi bi-envelope-paper me-2"></i>Send Invitations
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
                                <p class="mb-1 small">{{ \Illuminate\Support\Str::limit($log->content, 60) }}</p>
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
            .addClass(success ? 'alert-success' : 'alert-warning');
        
        icon.removeClass('bi-check-circle bi-x-circle bi-exclamation-triangle')
            .addClass(success ? 'bi-check-circle' : 'bi-exclamation-circle');
        
        // Set content with enhanced messaging
        title.text(success ? 'Validation Successful' : 'Validation Status');
        messageEl.html(`
            <div class="d-flex align-items-center">
                <strong class="me-2">${success ? '✓' : '!'}</strong>
                <div>
                    <strong>${message}</strong>
                    ${!success ? '<br><small class="text-muted">Please review the details below.</small>' : ''}
                </div>
            </div>
        `);
        
        // Add detailed results if available
        if (details && Array.isArray(details)) {
            details.forEach(detail => {
                const statusIcon = detail.status === 'success' ? '✓' : '!';
                const statusClass = detail.statusClass || (detail.status === 'success' ? 'success' : 'warning');
                const statusText = detail.status === 'success' ? 'Valid' : 
                    detail.status.replace(/_/g, ' ').toLowerCase();
                
                resultsTable.append(`
                    <tr>
                        <td>
                            <span class="badge bg-${statusClass}">
                                ${statusIcon} ${statusText}
                            </span>
                        </td>
                        <td>${detail.recipient}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <small class="text-${statusClass}">
                                    ${detail.message}
                                </small>
                                ${detail.debug ? `
                                    <button class="btn btn-link btn-sm ms-2 p-0" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#debug-${detail.recipient.replace(/\s+/g, '-')}" 
                                            aria-expanded="false">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                    <div class="collapse mt-1" id="debug-${detail.recipient.replace(/\s+/g, '-')}">
                                        <small class="text-muted">
                                            <pre class="mb-0">${JSON.stringify(detail.debug, null, 2)}</pre>
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
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
            showValidationResult(false, 'Please select at least one attendee to validate.', [{
                status: 'error',
                recipient: 'System',
                message: 'No attendees selected for validation.'
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
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                token: selectedAttendees.map(a => a.id).join(','),
                event_id: '{{ $event->id }}'
            },
            success: function(response) {
                // Log the complete response for debugging
                console.log('Complete Validation Response:', JSON.stringify(response, null, 2));
                
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
                    // Log the error response details
                    console.log('Error Response Details:', {
                        code: response.code,
                        message: response.message,
                        debug_info: response.debug_info,
                        attendee: response.attendee
                    });

                    // Handle different validation statuses from scanner
                    const status = response.code || 'invalid';
                    const statusClass = {
                        'TICKET_USED': 'warning',
                        'ALREADY_CHECKED_IN': 'warning',
                        'NO_TICKET': 'warning',
                        'INVALID_TOKEN': 'warning',
                        'NO_EVENT_SELECTED': 'warning',
                        'EVENT_NOT_FOUND': 'warning',
                        'VALIDATION_ERROR': 'danger'
                    }[status] || 'warning';

                    // Ensure we get the full message
                    const fullMessage = response.message || 'Token validation failed';
                    console.log('Full Message:', fullMessage);

                    // Create detailed status information
                    const details = selectedAttendees.map(attendee => {
                        // Include attendee information from response if available
                        const attendeeInfo = response.attendee ? 
                            `\nAttendee: ${response.attendee.name || 'Unknown'}` : '';
                        const eventInfo = response.attendee?.event ? 
                            `\nEvent: ${response.attendee.event.name || 'Unknown'}` : '';
                        const usedAtInfo = response.attendee?.checked_in_at ? 
                            `\nChecked in at: ${response.attendee.checked_in_at}` : '';

                        return {
                            status: status,
                            recipient: attendee.name,
                            message: `${fullMessage}${attendeeInfo}${eventInfo}${usedAtInfo}`,
                            statusClass: statusClass,
                            debug: response.debug_info || null
                        };
                    });

                    // Show the full validation result
                    showValidationResult(false, fullMessage, details);
                    
                    // Update status in the table with complete information
                    selectedAttendees.forEach(attendee => {
                        const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                        const statusCell = row.find('td:eq(4)');
                        const statusText = status === 'VALIDATION_ERROR' 
                            ? 'Validation Error' 
                            : status.replace(/_/g, ' ').toLowerCase();
                        
                        // Create a detailed tooltip with all available information
                        const tooltipContent = [
                            fullMessage,
                            response.attendee?.name ? `Attendee: ${response.attendee.name}` : null,
                            response.attendee?.event?.name ? `Event: ${response.attendee.event.name}` : null,
                            response.attendee?.checked_in_at ? `Checked in at: ${response.attendee.checked_in_at}` : null,
                            response.debug_info ? `Debug: ${JSON.stringify(response.debug_info)}` : null
                        ].filter(Boolean).join('\n');

                        statusCell.html(`
                            <span class="badge bg-${statusClass}" 
                                  title="${tooltipContent}"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  data-bs-html="true">
                                ${statusText}
                            </span>
                        `);
                    });

                    // Initialize tooltips with HTML support
                    $('[data-bs-toggle="tooltip"]').tooltip({
                        html: true,
                        template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner" style="white-space: pre-line; text-align: left;"></div></div>'
                    });
                }
            },
            error: function(xhr) {
                // Log the complete error response
                console.log('Complete Error Response:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });

                let status = 'VALIDATION_ERROR';
                let statusClass = 'danger';
                let message = 'Token validation failed';
                let debugInfo = null;
                let attendeeInfo = null;
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed Error Response:', JSON.stringify(response, null, 2));
                    
                    if (response.code) {
                        status = response.code;
                        statusClass = {
                            'TICKET_USED': 'warning',
                            'ALREADY_CHECKED_IN': 'warning',
                            'NO_TICKET': 'warning',
                            'INVALID_TOKEN': 'warning',
                            'NO_EVENT_SELECTED': 'warning',
                            'EVENT_NOT_FOUND': 'warning',
                            'VALIDATION_ERROR': 'danger'
                        }[status] || 'warning';
                        message = response.message || 'Token validation failed';
                        debugInfo = response.debug_info;
                        attendeeInfo = response.attendee;
                    } else if (response.error) {
                        status = 'VALIDATION_ERROR';
                        message = typeof response.error === 'string' 
                            ? response.error 
                            : 'An error occurred during validation';
                    } else if (response.message) {
                        message = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    console.error('Raw response:', xhr.responseText);
                    status = 'VALIDATION_ERROR';
                    message = 'Unable to process validation response';
                }

                // Create detailed status information
                const details = selectedAttendees.map(attendee => {
                    const attendeeDetails = attendeeInfo ? 
                        `\nAttendee: ${attendeeInfo.name || 'Unknown'}` : '';
                    const eventDetails = attendeeInfo?.event ? 
                        `\nEvent: ${attendeeInfo.event.name || 'Unknown'}` : '';
                    const usedAtDetails = attendeeInfo?.checked_in_at ? 
                        `\nChecked in at: ${attendeeInfo.checked_in_at}` : '';

                    return {
                        status: status,
                        recipient: attendee.name,
                        message: `${message}${attendeeDetails}${eventDetails}${usedAtDetails}`,
                        statusClass: statusClass,
                        debug: debugInfo
                    };
                });

                showValidationResult(false, message, details);
                
                // Update status in the table with complete information
                selectedAttendees.forEach(attendee => {
                    const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    const statusText = status === 'VALIDATION_ERROR' 
                        ? 'Validation Error' 
                        : status.replace(/_/g, ' ').toLowerCase();
                    
                    // Create a detailed tooltip with all available information
                    const tooltipContent = [
                        message,
                        attendeeInfo?.name ? `Attendee: ${attendeeInfo.name}` : null,
                        attendeeInfo?.event?.name ? `Event: ${attendeeInfo.event.name}` : null,
                        attendeeInfo?.checked_in_at ? `Checked in at: ${attendeeInfo.checked_in_at}` : null,
                        debugInfo ? `Debug: ${JSON.stringify(debugInfo)}` : null
                    ].filter(Boolean).join('\n');

                    statusCell.html(`
                        <span class="badge bg-${statusClass}" 
                              title="${tooltipContent}"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                              data-bs-html="true">
                            ${statusText}
                        </span>
                    `);
                });

                // Initialize tooltips with HTML support
                $('[data-bs-toggle="tooltip"]').tooltip({
                    html: true,
                    template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner" style="white-space: pre-line; text-align: left;"></div></div>'
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
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                token: attendeeId,
                event_id: '{{ $event->id }}'
            },
            success: function(response) {
                const alert = $('#singleSmsResultAlert');
                const icon = $('#singleSmsResultIcon');
                const title = $('#singleSmsResultTitle');
                const message = $('#singleSmsResultMessage');
                const details = $('#singleSmsResultDetails');
                
                if (response.success) {
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
                    // Handle different validation statuses from scanner
                    const status = response.code || 'invalid';
                    const statusClass = {
                        'TICKET_USED': 'warning',
                        'ALREADY_CHECKED_IN': 'warning',
                        'NO_TICKET': 'warning',
                        'INVALID_TOKEN': 'warning',
                        'NO_EVENT_SELECTED': 'warning',
                        'EVENT_NOT_FOUND': 'warning'
                    }[status] || 'warning';

                    const message = response.message || 'Token validation failed';
                    
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
                                <dd class="col-sm-8"><span class="badge bg-${statusClass}">${status.replace(/_/g, ' ').toLowerCase()}</span></dd>
                                <dt class="col-sm-4">Last Check</dt>
                                <dd class="col-sm-8">${new Date().toLocaleString()}</dd>
                            </dl>
                        </div>
                    `);
                    
                    // Update status in the table
                    const row = $(`.attendee-checkbox[value="${attendeeId}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    statusCell.html(`<span class="badge bg-${statusClass}">${status.replace(/_/g, ' ').toLowerCase()}</span>`);
                }
                
                $('#singleSmsResultsContainer').fadeIn(300);
            },
            error: function(xhr) {
                console.log('Single Validation Error Response:', xhr.responseText);
                const alert = $('#singleSmsResultAlert');
                const icon = $('#singleSmsResultIcon');
                const title = $('#singleSmsResultTitle');
                const message = $('#singleSmsResultMessage');
                const details = $('#singleSmsResultDetails');
                
                let status = 'VALIDATION_ERROR';
                let statusClass = 'danger';
                let errorMessage = 'Token validation failed';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed Single Error Response:', response);
                    
                    if (response.code) {
                        status = response.code;
                        statusClass = {
                            'TICKET_USED': 'warning',
                            'ALREADY_CHECKED_IN': 'warning',
                            'NO_TICKET': 'warning',
                            'INVALID_TOKEN': 'warning',
                            'NO_EVENT_SELECTED': 'warning',
                            'EVENT_NOT_FOUND': 'warning',
                            'VALIDATION_ERROR': 'danger'
                        }[status] || 'warning';
                        errorMessage = response.message || 'Token validation failed';
                    } else if (response.error) {
                        status = 'VALIDATION_ERROR';
                        errorMessage = typeof response.error === 'string' 
                            ? response.error 
                            : 'An error occurred during validation';
                    } else if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing single validation response:', e);
                    console.error('Raw single validation response:', xhr.responseText);
                    status = 'VALIDATION_ERROR';
                    errorMessage = 'Unable to process validation response';
                }
                
                alert.removeClass('alert-success alert-warning').addClass(`alert-${statusClass}`);
                icon.removeClass('bi-check-circle').addClass('bi-exclamation-circle');
                title.text('Validation Error');
                message.html(`
                    <strong>${errorMessage}</strong><br>
                    <small class="text-muted">Please check the validation details below.</small>
                `);
                details.html(`
                    <div class="alert alert-light border">
                        <h6 class="alert-heading mb-2">Validation Details</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Attendee</dt>
                            <dd class="col-sm-8">${attendeeName}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-${statusClass}" 
                                      title="${errorMessage}"
                                      data-bs-toggle="tooltip"
                                      data-bs-placement="top">
                                    ${status === 'VALIDATION_ERROR' ? 'Validation Error' : status.replace(/_/g, ' ').toLowerCase()}
                                </span>
                            </dd>
                            <dt class="col-sm-4">Last Check</dt>
                            <dd class="col-sm-8">${new Date().toLocaleString()}</dd>
                            <dt class="col-sm-4">Error Details</dt>
                            <dd class="col-sm-8 text-danger small">${errorMessage}</dd>
                        </dl>
                    </div>
                `);
                
                selectedAttendees.forEach(attendee => {
                    const row = $(`.attendee-checkbox[value="${attendee.id}"]`).closest('tr');
                    const statusCell = row.find('td:eq(4)');
                    statusCell.html(`
                        <span class="badge bg-${statusClass}" 
                              title="${errorMessage}"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top">
                            ${status === 'VALIDATION_ERROR' ? 'Validation Error' : status.replace(/_/g, ' ').toLowerCase()}
                        </span>
                    `);
                });

                $('[data-bs-toggle="tooltip"]').tooltip();
                
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

    // Send Invitation SMS to All Attendees
    $('#sendInvitationSmsBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        // Confirm action
        if (!confirm('This will send invitation SMS to ALL attendees who haven\'t received invitations yet. Are you sure you want to continue?')) {
            return;
        }
        
        // Show loading state
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Processing...');
        
        // Make AJAX request
        $.ajax({
            url: '{{ route("sms.management.event.dispatch-invitation-sms", $event) }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const alert = $(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Success!</strong> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    // Add statistics if available
                    if (response.statistics) {
                        const stats = response.statistics;
                        alert.append(`
                            <div class="mt-2 small">
                                <strong>Statistics:</strong><br>
                                • Total attendees: ${stats.total_attendees}<br>
                                • Attendees with phone: ${stats.attendees_with_phone}<br>
                                • Already sent invitations: ${stats.already_sent_count}<br>
                                • Will receive invitations: ${stats.will_be_sent_to}
                            </div>
                        `);
                    }
                    
                    // Insert alert at the top of the page
                    $('.container-fluid').prepend(alert);
                    
                    // Auto-remove alert after 10 seconds
                    setTimeout(() => {
                        alert.alert('close');
                    }, 10000);
                    
                } else {
                    // Show error message
                    const alert = $(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Error!</strong> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    $('.container-fluid').prepend(alert);
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while processing the request.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                const alert = $(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Error!</strong> ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('.container-fluid').prepend(alert);
            },
            complete: function() {
                // Reset button state
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
@endsection 