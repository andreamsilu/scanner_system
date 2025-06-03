@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Tickets</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tickets</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form id="filterForm" class="row g-3">
                                <div class="col-md-3">
                                    <select name="event_filter" id="eventFilter" class="form-select">
                                        <option value="">All Events</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="status" id="statusFilter" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="type" id="typeFilter" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="vip">VIP</option>
                                        <option value="regular">Regular</option>
                                        <option value="single">Single</option>
                                        <option value="double">Double</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <button type="reset" class="btn btn-secondary">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="ticketsTable" class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Attendee</th>
                                    <th>Event</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Last Scan</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        border-radius: 3px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 1em;
        text-align: center;
        z-index: 1000;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("events.events.tickets.index", ["event" => $event->id]) }}',
            data: function(d) {
                d.event_filter = $('#eventFilter').val();
                d.status = $('#statusFilter').val();
                d.type = $('#typeFilter').val();
            }
        },
        columns: [
            {
                data: 'attendee',
                render: function(data) {
                    return `<div>
                        <div class="fw-bold">${data.name}</div>
                        <small class="text-muted">${data.token}</small>
                    </div>`;
                }
            },
            {
                data: 'event',
                render: function(data) {
                    return `<a href="${data.url}" class="text-primary">${data.name}</a>`;
                }
            },
            {
                data: 'type',
                render: function(data) {
                    return `<span class="badge bg-${data.badge}">${data.type}</span>`;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    return `<span class="badge bg-${data.badge}">${data.status}</span>`;
                }
            },
            {
                data: 'last_scan',
                render: function(data) {
                    if (!data) return '<span class="text-muted">Never</span>';
                    return `<div>
                        <div>${data.time}</div>
                        <small class="text-muted">${data.full_time}</small>
                    </div>`;
                }
            },
            { data: 'created_at' },
            {
                data: 'actions',
                orderable: false,
                render: function(data) {
                    let html = `
                        <div class="btn-group">
                            <a href="${data.view_url}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="${data.print_url}" class="btn btn-sm btn-primary" title="Print" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="${data.export_url}" class="btn btn-sm btn-success" title="Export">
                                <i class="fas fa-file-export"></i>
                            </a>`;
                    
                    if (data.deactivate_url) {
                        html += `
                            <form action="${data.deactivate_url}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this ticket?');">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-danger" title="Deactivate">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>`;
                    }
                    
                    html += `</div>`;
                    return html;
                }
            }
        ],
        order: [[5, 'desc']], // Sort by created_at by default
        pageLength: 15,
        lengthMenu: [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
        language: {
            search: "Search tickets:",
            searchPlaceholder: "Search by name, token, or ticket number...",
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading tickets...',
            emptyTable: "No tickets found",
            zeroRecords: "No matching tickets found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-primary',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }
        ]
    });

    // Initialize buttons
    table.buttons().container().appendTo('#ticketsTable_wrapper .col-md-6:eq(0)');

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Handle filter changes
    $('#eventFilter, #statusFilter, #typeFilter').on('change', function() {
        table.ajax.reload();
    });

    // Handle form reset
    $('#filterForm').on('reset', function() {
        setTimeout(function() {
            table.ajax.reload();
        }, 0);
    });
});
</script>
@endpush
@endsection 