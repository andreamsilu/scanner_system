@extends('layouts.app')

@section('title', $event->name . ' - Tickets')

@section('content')
<div class="container-fluid">
    <!-- System Logo and Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="{{ asset('images/logo.png') }}" 
                             alt="Event System Logo" 
                             class="system-logo" 
                             style="height: 60px; width: auto;">
                    </div>
                    <div>
                        <h1 class="h3 mb-0"><i class="bi bi-ticket-perforated"></i> {{ $event->name }} - Tickets</h1>
                        <p class="text-muted"><i class="bi bi-calendar-event"></i> {{ $event->start_date->format('F j, Y') }} - {{ $event->end_date->format('F j, Y') }}</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Event
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateTicketsModal">
                        <i class="bi bi-plus-lg"></i> Generate Tickets
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-tag"></i> Ticket Type</label>
                    <select class="form-select" id="ticketTypeFilter">
                        <option value=""><i class="bi bi-funnel"></i> All Types</option>
                        <option value="vip"><i class="bi bi-star"></i> VIP</option>
                        <option value="regular"><i class="bi bi-ticket"></i> Regular</option>
                        <option value="single"><i class="bi bi-person"></i> Single</option>
                        <option value="double"><i class="bi bi-people"></i> Double</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-toggle-on"></i> Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value=""><i class="bi bi-funnel"></i> All Status</option>
                        <option value="active"><i class="bi bi-check-circle"></i> Active</option>
                        <option value="inactive"><i class="bi bi-x-circle"></i> Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-calendar-range"></i> Date Range</label>
                    <input type="text" class="form-control" id="dateRange" placeholder="Select date range">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" id="applyFilters">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="bulk-actions" style="display: none;">
                    <button class="btn btn-warning me-2" id="bulkDeactivate">
                        <i class="bi bi-x-circle"></i> Deactivate Selected
                    </button>
                    <button class="btn btn-success me-2" id="bulkActivate">
                        <i class="bi bi-check-circle"></i> Activate Selected
                    </button>
                    <button class="btn btn-danger" id="bulkDelete">
                        <i class="bi bi-trash"></i> Delete Selected
                    </button>
                </div>
                <div class="selected-count" style="display: none;">
                    <span class="badge bg-primary">0</span> tickets selected
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tickets-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th><i class="bi bi-upc-scan"></i> Ticket ID</th>
                            <th><i class="bi bi-person"></i> Attendee</th>
                            <th><i class="bi bi-tag"></i> Type</th>
                            <th><i class="bi bi-toggle-on"></i> Status</th>
                            <th><i class="bi bi-clock-history"></i> Last Scan</th>
                            <th><i class="bi bi-calendar-plus"></i> Created At</th>
                            <th><i class="bi bi-gear"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input ticket-checkbox" 
                                           value="{{ $ticket->id }}">
                                </td>
                                <td>{{ $ticket->ticket_id }}</td>
                                <td>
                                    @if($ticket->attendee)
                                        <a href="{{ route('attendees.show', $ticket->attendee) }}">
                                            {{ $ticket->attendee->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $ticketType = $ticket->type;
                                        $badgeClass = match($ticketType) {
                                            'vip' => 'bg-warning',
                                            'regular' => 'bg-secondary',
                                            'single' => 'bg-primary',
                                            'double' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        @php
                                            $typeIcon = match($ticketType) {
                                                'vip' => 'bi-star',
                                                'regular' => 'bi-ticket',
                                                'single' => 'bi-person',
                                                'double' => 'bi-people',
                                                default => 'bi-ticket'
                                            };
                                        @endphp
                                        <i class="bi {{ $typeIcon }}"></i> {{ ucfirst($ticketType) }}
                                    </span>
                                </td>
                                <td>
                                    @if($ticket->is_active)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->last_scan)
                                        <span title="{{ $ticket->last_scan->created_at->format('F j, Y g:i A') }}">
                                            <i class="bi bi-clock-history"></i> {{ $ticket->last_scan->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted"><i class="bi bi-clock"></i> Never</span>
                                    @endif
                                </td>
                                <td><i class="bi bi-calendar-plus"></i> {{ $ticket->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('tickets.show', $ticket) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('tickets.print', $ticket) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Print Ticket"
                                           target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        @if($ticket->is_active)
                                            <form action="{{ route('tickets.deactivate', $ticket) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-warning" 
                                                        title="Deactivate">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No tickets found for this event.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Generate Tickets Modal -->
@include('tickets.partials.generate-modal')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
    .system-logo {
        object-fit: contain;
        transition: transform 0.2s ease-in-out;
    }
    .system-logo:hover {
        transform: scale(1.05);
    }
    @media (max-width: 768px) {
        .system-logo {
            height: 40px !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#tickets-table').DataTable({
        responsive: true,
        processing: true,
        pageLength: 15,
        order: [[6, 'desc']], // Sort by created_at by default
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="bi bi-download"></i> Export',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-info',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    }
                ]
            }
        ],
        columnDefs: [
            { orderable: false, targets: [0, 7] }, // Disable sorting on checkbox and actions columns
            { responsivePriority: 1, targets: [1, 2, 7] }, // Ticket ID, Attendee, and Actions are most important
            { responsivePriority: 2, targets: [3, 4] }, // Type and Status are second priority
            { responsivePriority: 3, targets: [5, 6] }  // Last Scan and Created At are least priority
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search tickets...",
            emptyTable: "No tickets found for this event.",
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Initialize DateRangePicker
    $('#dateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        }
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Handle Select All Checkbox
    $('#selectAll').change(function() {
        $('.ticket-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });

    // Handle Individual Checkboxes
    $('.ticket-checkbox').change(function() {
        updateBulkActions();
    });

    // Update Bulk Actions Visibility
    function updateBulkActions() {
        const selectedCount = $('.ticket-checkbox:checked').length;
        $('.selected-count span').text(selectedCount);
        $('.bulk-actions, .selected-count').toggle(selectedCount > 0);
    }

    // Apply Filters
    $('#applyFilters').click(function() {
        const typeFilter = $('#ticketTypeFilter').val();
        const statusFilter = $('#statusFilter').val();
        const dateRange = $('#dateRange').val();

        table.column(3).search(typeFilter);
        table.column(4).search(statusFilter);
        
        if (dateRange) {
            const dates = dateRange.split(' - ');
            table.draw();
        }

        table.draw();
    });

    // Bulk Actions
    $('#bulkDeactivate').click(function() {
        if (confirm('Are you sure you want to deactivate selected tickets?')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk deactivate logic here
            console.log('Deactivating tickets:', selectedIds);
        }
    });

    $('#bulkActivate').click(function() {
        if (confirm('Are you sure you want to activate selected tickets?')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk activate logic here
            console.log('Activating tickets:', selectedIds);
        }
    });

    $('#bulkDelete').click(function() {
        if (confirm('Are you sure you want to delete selected tickets? This action cannot be undone.')) {
            const selectedIds = $('.ticket-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            // Add your bulk delete logic here
            console.log('Deleting tickets:', selectedIds);
        }
    });
});
</script>
@endpush
@endsection 