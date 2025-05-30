@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <x-page-header 
        title="SMS Management"
        :breadcrumbs="['SMS Management' => null]"
    />

    <!-- SMS Stats Cards -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Total SMS Sent</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::count() }}</h5>
                            <small class="text-muted small">All time</small>
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
                            <h6 class="card-subtitle mb-0 text-muted small">Successful SMS</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::where('status', 'delivered')->count() }}</h5>
                            <small class="text-muted small">Delivered messages</small>
                        </div>
                        <div class="p-1 rounded-circle bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
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
                            <h6 class="card-subtitle mb-0 text-muted small">Failed SMS</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::where('status', 'failed')->count() }}</h5>
                            <small class="text-muted small">Failed deliveries</small>
                        </div>
                        <div class="p-1 rounded-circle bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-x-circle"></i>
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
                            <h6 class="card-subtitle mb-0 text-muted small">Active Templates</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsTemplate::where('is_active', true)->count() }}</h5>
                            <small class="text-muted small">Available templates</small>
                        </div>
                        <div class="p-1 rounded-circle bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-file-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Events with SMS Activity</h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                    <i class="bi bi-plus-circle me-2"></i>New Template
                </button>
                <a href="{{ route('sms.management.templates') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-gear me-2"></i>Manage Templates
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="eventsTable">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Attendees</th>
                            <th>SMS Sent</th>
                            <th>Success Rate</th>
                            <th>Last SMS</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>{{ $event->name }}</td>
                                <td>{{ $event->date->format('M d, Y g:i A') }}</td>
                                <td>{{ $event->attendees_count }}</td>
                                <td>{{ $event->sms_logs_count }}</td>
                                <td>
                                    @php
                                        $successRate = $event->sms_logs_count > 0 
                                            ? round(($event->sms_logs->where('status', 'delivered')->count() / $event->sms_logs_count) * 100, 1)
                                            : 0;
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $successRate }}%">
                                            </div>
                                        </div>
                                        <span class="ms-2 small">{{ $successRate }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @if($event->sms_logs->isNotEmpty())
                                        {{ $event->sms_logs->first()->created_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">No SMS sent</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('sms.management.event', $event) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-chat-dots me-1"></i>Manage SMS
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                data-bs-toggle="dropdown">
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('sms.management.event', $event) }}?view=logs">
                                                    <i class="bi bi-clock-history me-2"></i>View Logs
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('sms.management.event', $event) }}?view=export">
                                                    <i class="bi bi-download me-2"></i>Export Logs
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

<!-- New Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('sms.management.templates.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New SMS Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="attendee_invitation">Attendee Invitation</option>
                            <option value="ticket_used">Ticket Usage Notification</option>
                            <option value="ticket_reminder">Event Reminder</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="4" maxlength="160" required></textarea>
                        <small class="text-muted">Available variables: {user_name}, {event_name}, {date}, {location}, {ticket_number}, {ticket_type}, {ticket_price}</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Template</button>
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
    .progress {
        width: 60px;
        background-color: #e9ecef;
    }
    .dropdown-menu {
        min-width: 200px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#eventsTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 10,
        language: {
            search: "",
            searchPlaceholder: "Search events..."
        },
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });

    // Character counter for template content
    $('textarea[name="content"]').on('input', function() {
        const remaining = 160 - $(this).val().length;
        $(this).next('small').text(`Characters remaining: ${remaining}`);
    });
});
</script>
@endpush
@endsection 