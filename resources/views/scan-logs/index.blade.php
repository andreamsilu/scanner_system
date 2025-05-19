@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Scan Logs</h2>
        <a href="{{ route('scan-logs.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Scan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover" id="scanLogsTable">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Scanned At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($scanLogs as $log)
                    <tr>
                        <td>
                            @if($log->event->banner)
                                <img src="{{ $log->event->banner_url }}" alt="{{ $log->event->name }}" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                            @endif
                            {{ $log->event->name }}
                            <div class="text-muted small">{{ $log->event->venue }}</div>
                        </td>
                        <td>
                            <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                            {{ $log->user->name }}
                            <div class="text-muted small">{{ $log->user->email }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->status_badge }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $log->created_at->format('M d, Y') }}
                            <div class="text-muted small">{{ $log->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('scan-logs.show', $log) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('scan-logs.destroy', $log) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete this scan log?');">
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
                        <td colspan="5" class="text-center">No scan logs found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.1.2/css/dataTables.dateTime.min.css">
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
<script src="https://cdn.datatables.net/datetime/1.1.2/js/dataTables.dateTime.min.js"></script>
<script src="{{ asset('js/datatables-config.js') }}"></script>

<script>
    $(document).ready(function() {
        const table = initDataTable('#scanLogsTable', {
            order: [[3, 'desc']], // Sort by scanned at by default
            columnDefs: [
                { orderable: false, targets: [4] } // Disable sorting on actions column
            ]
        });

        // Add custom filtering
        $('<div class="row mb-3"><div class="col-md-3"><select class="form-select" id="eventFilter"><option value="">All Events</option>@foreach($events as $event)<option value="{{ $event->id }}">{{ $event->name }}</option>@endforeach</select></div><div class="col-md-3"><select class="form-select" id="statusFilter"><option value="">All Status</option><option value="success">Success</option><option value="failed">Failed</option></select></div></div>').insertBefore(table.table().container());

        // Event Filter
        $('#eventFilter').on('change', function() {
            table.column(0).search(this.value).draw();
        });

        // Status Filter
        $('#statusFilter').on('change', function() {
            table.column(2).search(this.value).draw();
        });
    });
</script>
@endpush
@endsection 