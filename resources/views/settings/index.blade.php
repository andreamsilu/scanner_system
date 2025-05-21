<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">System Settings</h2>
                <p class="text-muted mb-0">View and manage system information, logs, and settings</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('settings.clear-cache') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to clear the cache?')">
                        <i class="bi bi-broom me-2"></i>Clear Cache
                    </button>
                </form>
                <form action="{{ route('settings.clear-logs') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear the logs?')">
                        <i class="bi bi-trash me-2"></i>Clear Logs
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <!-- System Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Laravel Version</dt>
                        <dd class="col-sm-8">{{ app()->version() }}</dd>

                        <dt class="col-sm-4">PHP Version</dt>
                        <dd class="col-sm-8">{{ PHP_VERSION }}</dd>

                        <dt class="col-sm-4">Server Software</dt>
                        <dd class="col-sm-8">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Database</dt>
                        <dd class="col-sm-8">{{ config('database.default') }}</dd>

                        <dt class="col-sm-4">Environment</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ app()->environment('production') ? 'danger' : 'info' }}">
                                {{ app()->environment() }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Application Statistics</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Total Users</dt>
                        <dd class="col-sm-8">{{ $stats['total_users'] ?? 0 }}</dd>

                        <dt class="col-sm-4">Active Events</dt>
                        <dd class="col-sm-8">{{ $stats['active_events'] ?? 0 }}</dd>

                        <dt class="col-sm-4">Total Attendees</dt>
                        <dd class="col-sm-8">{{ $stats['total_attendees'] ?? 0 }}</dd>

                        <dt class="col-sm-4">Total Scans</dt>
                        <dd class="col-sm-8">{{ $stats['total_scans'] ?? 0 }}</dd>

                        <dt class="col-sm-4">Last Scan</dt>
                        <dd class="col-sm-8">{{ $stats['last_scan'] ? $stats['last_scan']->diffForHumans() : 'Never' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Activity Logs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Date</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td>{{ $log->causer?->name ?? 'System' }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-link" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#logModal{{ $log->id }}">
                                        View Details
                                    </button>
                                </td>
                            </tr>

                            <!-- Log Details Modal -->
                            <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Log Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <pre class="mb-0"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Laravel System Logs -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Laravel System Logs</h5>
            <form action="{{ route('settings.clear-logs') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to clear the Laravel logs?')">
                    <i class="bi bi-trash me-2"></i>Clear Logs
                </button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Log Entry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap" style="width: 200px;">
                                    @php
                                        preg_match('/\[(.*?)\]/', $log, $matches);
                                        $timestamp = $matches[1] ?? '';
                                    @endphp
                                    {{ $timestamp }}
                                </td>
                                <td>
                                    <code class="text-wrap">{{ $log }}</code>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">No system logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Server Logs -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Server Logs</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="serverLogsTabs" role="tablist">
                @if(isset($serverLogs['apache']))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="apache-tab" data-bs-toggle="tab" data-bs-target="#apache-logs" type="button" role="tab">
                            Apache Logs
                        </button>
                    </li>
                @endif
                @if(isset($serverLogs['nginx']))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ !isset($serverLogs['apache']) ? 'active' : '' }}" id="nginx-tab" data-bs-toggle="tab" data-bs-target="#nginx-logs" type="button" role="tab">
                            Nginx Logs
                        </button>
                    </li>
                @endif
            </ul>

            <div class="tab-content mt-3" id="serverLogsContent">
                @if(isset($serverLogs['apache']))
                    <div class="tab-pane fade show active" id="apache-logs" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Log Entry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serverLogs['apache'] as $log)
                                        <tr>
                                            <td class="text-nowrap" style="width: 200px;">
                                                @php
                                                    preg_match('/\[(.*?)\]/', $log, $matches);
                                                    $timestamp = $matches[1] ?? '';
                                                @endphp
                                                {{ $timestamp }}
                                            </td>
                                            <td>
                                                <code class="text-wrap">{{ $log }}</code>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(isset($serverLogs['nginx']))
                    <div class="tab-pane fade {{ !isset($serverLogs['apache']) ? 'show active' : '' }}" id="nginx-logs" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Log Entry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serverLogs['nginx'] as $log)
                                        <tr>
                                            <td class="text-nowrap" style="width: 200px;">
                                                @php
                                                    preg_match('/\[(.*?)\]/', $log, $matches);
                                                    $timestamp = $matches[1] ?? '';
                                                @endphp
                                                {{ $timestamp }}
                                            </td>
                                            <td>
                                                <code class="text-wrap">{{ $log }}</code>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(empty($serverLogs))
                    <div class="alert alert-info mt-3">
                        No server logs found. Make sure the web server logs are accessible.
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .table code {
            white-space: pre-wrap;
            word-break: break-word;
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: 500;
        }
    </style>
    @endpush
</x-app-layout> 