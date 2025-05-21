<x-layouts.app>
    <x-slot:header>
        <h2 class="display-6 fw-bold text-dark">Scan History</h2>
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('scanner.history.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('scanner.history.export', array_merge(request()->query(), ['format' => 'excel'])) }}">
                                    <i class="bi bi-file-earmark-excel me-2"></i>Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('scanner.history.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('scanner.history') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="event_id" class="form-label">Event</label>
                        <select class="form-select" id="event_id" name="event_id">
                            <option value="">All Events</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>
                            Apply Filters
                        </button>
                        <a href="{{ route('scanner.history') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Scan History Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Attendee</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Scanned By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scans as $scan)
                                <tr>
                                    <td>{{ $scan->created_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ $scan->attendee->name }}</td>
                                    <td>{{ $scan->event->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                            {{ ucfirst($scan->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $scan->scanned_by }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-history fa-2x mb-3"></i>
                                            <p class="mb-0">No scan history found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $scans->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

@push('styles')
<style>
/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length select {
    padding: 0.25rem 2rem 0.25rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.25rem 0.5rem;
    margin: 0 0.25rem;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #0d6efd;
    color: white !important;
    border: none;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #0b5ed7;
    color: white !important;
    border: none;
}

/* Filter Form Styling */
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    $('#scanHistoryTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']],
        language: {
            search: "",
            searchPlaceholder: "Search scan history...",
            emptyTable: "No scan records found",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            infoEmpty: "Showing 0 to 0 of 0 records",
            infoFiltered: "(filtered from _MAX_ total records)",
            lengthMenu: "Show _MENU_ records per page",
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                previous: '<i class="fas fa-angle-left"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                last: '<i class="fas fa-angle-double-right"></i>'
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        columnDefs: [
            { orderable: false, targets: -1 }
        ],
        responsive: true,
        stateSave: true,
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fas fa-download me-2"></i>Export',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy me-2"></i>Copy',
                        className: 'btn btn-secondary'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel me-2"></i>Excel',
                        className: 'btn btn-success'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                        className: 'btn btn-danger'
                    }
                ]
            }
        ]
    });
});
</script>
@endpush 