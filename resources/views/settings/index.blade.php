<x-app-layout>
    <x-page-header 
        title="System Settings"
        subtitle="View and manage system information, logs, and settings"
        :breadcrumbs="['Settings' => null]"
    >
        <x-slot name="actions">
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
        </x-slot>
    </x-page-header>

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
            @if(!empty($serverLogs))
                <ul class="nav nav-tabs" id="serverLogsTabs" role="tablist">
                    @php $firstTab = true; @endphp
                    @foreach($serverLogs as $server => $logTypes)
                        @foreach($logTypes as $type => $logs)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $firstTab ? 'active' : '' }}" 
                                        id="{{ $server }}-{{ $type }}-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#{{ $server }}-{{ $type }}-logs" 
                                        type="button" 
                                        role="tab">
                                    {{ ucfirst($server) }} {{ ucfirst($type) }} Logs
                                </button>
                            </li>
                            @php $firstTab = false; @endphp
                        @endforeach
                    @endforeach
                </ul>

                <div class="tab-content mt-3" id="serverLogsContent">
                    @php $firstTab = true; @endphp
                    @foreach($serverLogs as $server => $logTypes)
                        @foreach($logTypes as $type => $logs)
                            <div class="tab-pane fade {{ $firstTab ? 'show active' : '' }}" 
                                 id="{{ $server }}-{{ $type }}-logs" 
                                 role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Timestamp</th>
                                                <th>Log Entry</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($logs as $log)
                                                <tr>
                                                    <td class="text-nowrap" style="width: 200px;">
                                                        @php
                                                            // Try different timestamp formats
                                                            $timestamp = '';
                                                            if (preg_match('/\[(.*?)\]/', $log, $matches)) {
                                                                $timestamp = $matches[1];
                                                            } elseif (preg_match('/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $log, $matches)) {
                                                                $timestamp = $matches[1];
                                                            } elseif (preg_match('/(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2})/', $log, $matches)) {
                                                                $timestamp = $matches[1];
                                                            }
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
                            @php $firstTab = false; @endphp
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <h5 class="alert-heading">No Server Logs Found</h5>
                    <p class="mb-0">No server logs were found or accessible. This could be because:</p>
                    <ul class="mb-0 mt-2">
                        <li>The web server is not installed</li>
                        <li>The log files are not in the default locations</li>
                        <li>The application doesn't have permission to read the log files</li>
                    </ul>
                    <hr>
                    <p class="mb-0">You can add custom log paths in your <code>.env</code> file using the <code>CUSTOM_LOG_PATHS</code> variable, separated by commas.</p>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
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