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
                    <label for="event" class="form-label">Event</label>
                    <select name="event" id="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach(\App\Models\Event::orderBy('name')->get() as $event)
                            <option value="{{ $event->id }}" {{ request('event') == $event->id ? 'selected' : '' }}>
                                {{ $event->name }}
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
                            <th>Event</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Reference</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $metadata = $log->metadata ?? [];
                                $smsType = $metadata['type'] ?? 'unknown';
                                $eventName = $metadata['event_name'] ?? null;
                                $attendeeName = $metadata['attendee_name'] ?? null;
                                $attendeePhone = $metadata['attendee_phone'] ?? $log->recipient;
                            @endphp
                            <tr>
                                <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    @if($attendeeName)
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $attendeeName }}</span>
                                            <small class="text-muted">Attendee</small>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $attendeePhone }}</span>
                                        @if($attendeePhone !== $log->recipient)
                                            <small class="text-muted">Original: {{ $log->recipient }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $smsType === 'invitation' ? 'primary' : ($smsType === 'reminder' ? 'warning' : ($smsType === 'notification' ? 'info' : 'secondary')) }}">
                                        {{ ucfirst($smsType) }}
                                    </span>
                                </td>
                                <td>
                                    @if($eventName)
                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                              data-bs-toggle="tooltip" title="{{ $eventName }}">
                                            {{ $eventName }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->getStatusBadgeClass() }}">
                                        {{ $log->getStatusMessage() }}
                                    </span>
                                    @if($log->provider_status && $log->provider_status !== $log->status)
                                        <small class="text-muted d-block">
                                            Provider: {{ ucfirst($log->provider_status) }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                          data-bs-toggle="tooltip" title="{{ $log->message }}">
                                        {{ $log->message }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->reference)
                                        <small class="text-muted">{{ $log->reference }}</small>
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
</x-app-layout> 
