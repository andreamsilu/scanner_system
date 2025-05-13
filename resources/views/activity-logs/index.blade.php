@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Activity Logs</h2>
        <a href="{{ route('activity-logs.export') }}" class="btn btn-success">
            <i class="fas fa-download me-2"></i>Export Logs
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-3">
            <select class="form-select" id="userFilter">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" id="searchFilter" placeholder="Search logs...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="activityLogsTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->user->name ?? 'System' }}</td>
                        <td>
                            <span class="badge bg-{{ $log->action_type === 'create' ? 'success' : ($log->action_type === 'update' ? 'warning' : ($log->action_type === 'delete' ? 'danger' : 'info')) }}">
                                {{ ucfirst($log->action_type) }}
                            </span>
                        </td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td>{{ Str::limit($log->user_agent, 50) }}</td>
                        <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No activity logs found.</td>
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
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.4/css/select.bootstrap5.min.css">
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
    .avatar {
        width: 32px;
        height: 32px;
        overflow: hidden;
    }
    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
<script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
<script src="{{ asset('js/datatables-config.js') }}"></script>

<script>
    $(document).ready(function() {
        const table = initDataTable('#activityLogsTable', {
            order: [[5, 'desc']], // Sort by created_at by default
            columnDefs: [
                { orderable: false, targets: [4] } // Disable sorting on user agent column
            ]
        });

        // User Filter
        $('#userFilter').on('change', function() {
            table.column(0).search(this.value).draw();
        });

        // Date Filter
        $('#dateFilter').on('change', function() {
            table.column(5).search(this.value).draw();
        });

        // Search Filter
        $('#searchFilter').on('keyup', function() {
            table.search(this.value).draw();
        });
    });
</script>
@endpush
@endsection 