<x-app-layout>
    <x-slot:title>
        SMS Logs
    </x-slot>

    <x-page-header 
        title="SMS Logs"
        :breadcrumbs="[
            'SMS Management' => route('sms.management.index'),
            'Logs' => null
        ]"
    />

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('sms.management.logs') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="invitation" {{ request('type') === 'invitation' ? 'selected' : '' }}>Invitation</option>
                        <option value="notification" {{ request('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                        <option value="reminder" {{ request('type') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                        <option value="test" {{ request('type') === 'test' ? 'selected' : '' }}>Test</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="provider" class="form-label">Provider</label>
                    <select name="provider" id="provider" class="form-select">
                        <option value="">All Providers</option>
                        @foreach(\App\Models\SmsLog::distinct()->pluck('provider') as $provider)
                            <option value="{{ $provider }}" {{ request('provider') === $provider ? 'selected' : '' }}>
                                {{ ucfirst($provider) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search phone or message..." value="{{ request('search') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                    <a href="{{ route('sms.management.logs') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Provider</th>
                            <th>Message</th>
                            <th>Message ID</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    @if($log->attendee_name)
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $log->attendee_name }}</span>
                                            <small class="text-muted">Attendee</small>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $log->attendee_phone }}</span>
                                        @if($log->attendee_phone !== $log->to)
                                            <small class="text-muted">Original: {{ $log->to }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->type === 'invitation' ? 'primary' : ($log->type === 'reminder' ? 'warning' : 'info') }}">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ ucfirst($log->provider) }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                          data-bs-toggle="tooltip" title="{{ $log->message }}">
                                        {{ $log->message }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->message_id)
                                        <small class="text-muted">{{ $log->message_id }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->error)
                                        <span class="text-danger" data-bs-toggle="tooltip" title="{{ $log->error }}">
                                            <i class="bi bi-exclamation-circle"></i>
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-chat-dots fs-4 d-block mb-2"></i>
                                        No SMS logs found
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
    @endpush
</x-app-layout> 