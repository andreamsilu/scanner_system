<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">
                    @if(auth()->user()->hasRole('admin'))
                        Available Events
                    @else
                        Your Assigned Events
                    @endif
                </h2>
                <p class="text-muted mb-0">
                    @if(auth()->user()->hasRole('admin'))
                        Select any event to start scanning attendees
                    @else
                        Select an event to start scanning attendees
                    @endif
                </p>
            </div>
            <a href="{{ route('scanner.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="row">
        @forelse($events as $event)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm me-2">
                                <span class="avatar-title rounded-circle bg-primary bg-opacity-10 text-primary">
                                    {{ substr($event->name, 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">{{ $event->name }}</h5>
                                <small class="text-muted">Assigned {{ $event->pivot->assigned_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <p class="card-text text-muted">
                            <i class="bi bi-calendar-event me-2"></i>
                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                        </p>
                        <p class="card-text">
                            <i class="bi bi-geo-alt me-2"></i>
                            {{ $event->location }}
                        </p>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Total</small>
                                    <strong>{{ $event->scan_stats['total_attendees'] }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Scanned</small>
                                    <strong class="text-success">{{ $event->scan_stats['scanned_count'] }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Pending</small>
                                    <strong class="text-warning">{{ $event->scan_stats['pending_count'] }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            @php
                                $statusClass = match($event->status) {
                                    'active' => 'success',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }}">
                                {{ ucfirst($event->status) }}
                            </span>
                            <div class="btn-group">
                                <a href="{{ route('events.show', $event) }}" 
                                   class="btn btn-info btn-sm">
                                    <i class="bi bi-eye me-1"></i>View Attendees
                                </a>
                                <a href="{{ route('scanner.select-event', ['event' => $event->id]) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-upc-scan me-1"></i>Start Scanning
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-calendar-x fa-3x mb-3"></i>
                            <h5>
                                @if(auth()->user()->hasRole('admin'))
                                    No Active Events
                                @else
                                    No Assigned Events
                                @endif
                            </h5>
                            <p class="mb-0">
                                @if(auth()->user()->hasRole('admin'))
                                    There are no active events available at the moment.
                                @else
                                    You have not been assigned to any events yet. Please contact your administrator.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $events->links() }}
    </div>
</x-app-layout> 