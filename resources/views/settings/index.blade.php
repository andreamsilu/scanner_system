@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h3 mb-0">System Settings</h2>
                    <p class="text-muted mb-0">View and manage system information, logs, and settings</p>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route('settings.clear-cache') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to clear the cache?')">
                            <i class="fas fa-broom me-2"></i>Clear Cache
                        </button>
                    </form>
                    <form action="{{ route('settings.clear-logs') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear the logs?')">
                            <i class="fas fa-trash me-2"></i>Clear Logs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="200">PHP Version</th>
                                        <td>{{ $systemInfo['php_version'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Laravel Version</th>
                                        <td>{{ $systemInfo['laravel_version'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Server Software</th>
                                        <td>{{ $systemInfo['server_software'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Server OS</th>
                                        <td>{{ $systemInfo['server_os'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Database Connection</th>
                                        <td>{{ $systemInfo['database_connection'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Timezone</th>
                                        <td>{{ $systemInfo['timezone'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="200">Environment</th>
                                        <td>
                                            <span class="badge bg-{{ $systemInfo['environment'] === 'production' ? 'success' : 'warning' }}">
                                                {{ $systemInfo['environment'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Debug Mode</th>
                                        <td>
                                            <span class="badge bg-{{ $systemInfo['debug_mode'] === 'Enabled' ? 'danger' : 'success' }}">
                                                {{ $systemInfo['debug_mode'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Maintenance Mode</th>
                                        <td>
                                            <span class="badge bg-{{ $systemInfo['maintenance_mode'] === 'Enabled' ? 'warning' : 'success' }}">
                                                {{ $systemInfo['maintenance_mode'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Memory Limit</th>
                                        <td>{{ $systemInfo['memory_limit'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Max Execution Time</th>
                                        <td>{{ $systemInfo['max_execution_time'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Upload Max Filesize</th>
                                        <td>{{ $systemInfo['upload_max_filesize'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Database Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-0">
                                <strong>Total Tables:</strong> {{ $databaseInfo['total_tables'] }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">
                                <strong>Total Size:</strong> {{ $databaseInfo['total_size'] }}
                            </p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($databaseInfo['tables'] as $table)
                                    <tr>
                                        <td>{{ $table['name'] }}</td>
                                        <td>{{ number_format($table['rows']) }}</td>
                                        <td>{{ $table['size'] }}</td>
                                        <td>{{ $table['created'] }}</td>
                                        <td>{{ $table['updated'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">
                                            @if(isset($databaseInfo['error']))
                                                <div class="text-danger">{{ $databaseInfo['error'] }}</div>
                                            @else
                                                No tables found
                                            @endif
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

    <!-- Cache Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Cache Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th width="200">Driver</th>
                                        <td>{{ $cacheInfo['driver'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Prefix</th>
                                        <td>{{ $cacheInfo['prefix'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-{{ $cacheInfo['status'] === 'Connected' ? 'success' : 'danger' }}">
                                                {{ $cacheInfo['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Available Stores</h6>
                            <ul class="list-unstyled">
                                @forelse($cacheInfo['stores'] as $store)
                                    <li>
                                        <span class="badge bg-info">{{ $store }}</span>
                                    </li>
                                @empty
                                    <li class="text-muted">No stores available</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Logs -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">System Logs</h5>
                    <small class="text-muted">Showing last 1000 entries</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Level</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    @php
                                        $logData = json_decode($log, true);
                                        $timestamp = $logData['timestamp'] ?? '';
                                        $level = $logData['level'] ?? 'info';
                                        $message = $logData['message'] ?? $log;
                                    @endphp
                                    <tr>
                                        <td>{{ $timestamp }}</td>
                                        <td>
                                            <span class="badge bg-{{ $level === 'error' ? 'danger' : ($level === 'warning' ? 'warning' : 'info') }}">
                                                {{ strtoupper($level) }}
                                            </span>
                                        </td>
                                        <td class="text-wrap" style="max-width: 600px;">{{ $message }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No logs found
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
<style>
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Auto-scroll logs to bottom
        const logsTable = $('.table-responsive');
        if (logsTable.length) {
            logsTable.scrollTop(logsTable[0].scrollHeight);
        }
    });
</script>
@endpush
@endsection 