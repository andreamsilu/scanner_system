@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Events</h5>
                        <a href="{{ route('events.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="capacityFilter">
                                <option value="">All Capacity</option>
                                <option value="available">Available</option>
                                <option value="full">Full</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="eventsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Venue</th>
                                    <th>Date</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($events as $event)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($event->banner)
                                                    <img src="{{ $event->banner_url }}" alt="{{ $event->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $event->name }}</h6>
                                                    <small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $event->venue }}</td>
                                        <td>
                                            <div>{{ $event->start_date->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $event->start_date->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $event->remaining_capacity }} / {{ $event->capacity }}</div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: {{ ($event->capacity - $event->remaining_capacity) / $event->capacity * 100 }}%">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $event->status_badge }}">
                                                {{ ucfirst($event->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('events.show', $event) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('events.edit', $event) }}" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('events.destroy', $event) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                                <p>No events found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/datetime/1.1.2/css/dataTables.dateTime.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.3.4/css/select.bootstrap5.min.css">
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px;
        margin: 0 2px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #0a58ca;
        border-color: #0a58ca;
        color: white !important;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #dee2e6;
    }
    .dt-buttons .btn {
        margin-right: 0.5rem;
    }
    .dt-button-collection {
        padding: 0.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .dt-button-collection .dt-button {
        margin: 0.25rem;
        border-radius: 0.25rem;
    }
    .filter-section {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/datetime/1.1.2/js/dataTables.dateTime.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#eventsTable').DataTable({
            order: [[2, 'asc']], // Sort by date column by default
            pageLength: 10,
            dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="fas fa-download me-2"></i>Export',
                    className: 'btn btn-success',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel me-2"></i>Excel',
                            className: 'btn btn-success',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                            className: 'btn btn-danger',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print me-2"></i>Print',
                            className: 'btn btn-info',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        }
                    ]
                }
            ],
            language: {
                search: "",
                searchPlaceholder: "Search events...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            select: {
                style: 'multi',
                selector: 'td:not(:last-child)'
            }
        });

        // Status Filter
        $('#statusFilter').on('change', function() {
            table.column(4).search(this.value).draw();
        });

        // Capacity Filter
        $('#capacityFilter').on('change', function() {
            if (this.value === 'available') {
                table.column(3).search('>0', true, false).draw();
            } else if (this.value === 'full') {
                table.column(3).search('0', true, false).draw();
            } else {
                table.column(3).search('').draw();
            }
        });

        // Date Filter
        $('#dateFilter').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        // Custom search function for complex columns
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            var status = $('#statusFilter').val();
            var capacity = $('#capacityFilter').val();
            var date = $('#dateFilter').val();

            if (status && data[4].indexOf(status) === -1) {
                return false;
            }

            if (capacity) {
                var capacityData = data[3].split('/');
                var remaining = parseInt(capacityData[0]);
                if (capacity === 'available' && remaining <= 0) {
                    return false;
                }
                if (capacity === 'full' && remaining > 0) {
                    return false;
                }
            }

            if (date && data[2].indexOf(date) === -1) {
                return false;
            }

            return true;
        });
    });
</script>
@endpush
@endsection 