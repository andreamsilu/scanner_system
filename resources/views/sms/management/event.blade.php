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
                            <h5 class="card-title mb-0">{{ $event->sms_logs_count }}</h5>
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
                                    $successRate = $event->sms_logs_count > 0 
                                        ? round(($event->sms_logs->where('status', 'delivered')->count() / $event->sms_logs_count) * 100, 1)
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
                            <h5 class="card-title mb-0">{{ $event->attendees_count }}</h5>
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
                                @if($event->sms_logs->isNotEmpty())
                                    {{ $event->sms_logs->first()->created_at->format('M d, g:i A') }}
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
                                                        data-attendee-phone="{{ $attendee->phone_number }}">
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
            <!-- Templates -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Available Templates</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($templates as $template)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $template->name }}</h6>
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p class="mb-1 small text-muted">{{ Str::limit($template->content, 60) }}</p>
                                <small class="text-muted">Type: {{ ucfirst(str_replace('_', ' ', $template->type)) }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

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
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="selectedCount">0</span> attendees selected
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Only attendees with valid phone numbers will receive SMS.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send SMS</button>
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
        // Update select all checkbox
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

    // Send SMS Form handling
    $('#sendSmsForm').on('submit', function(e) {
        e.preventDefault();
        const selectedAttendees = $('.attendee-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedAttendees.length === 0) {
            alert('Please select at least one attendee');
            return;
        }

        const formData = new FormData(this);
        formData.append('attendee_ids', JSON.stringify(selectedAttendees));

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(`Successfully sent ${response.success_count} messages. ${response.failure_count} failed.`);
                    location.reload();
                } else {
                    alert('Error sending messages: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error sending messages. Please try again.');
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