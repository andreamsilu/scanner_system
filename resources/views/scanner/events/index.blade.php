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
                        <h5 class="card-title">{{ $event->name }}</h5>
                        <p class="card-text text-muted">
                            <i class="bi bi-calendar-event me-2"></i>
                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                        </p>
                        <p class="card-text">
                            <i class="bi bi-geo-alt me-2"></i>
                            {{ $event->location }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-{{ $event->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                            <div class="btn-group">
                                <a href="{{ route('events.show', $event) }}" 
                                   class="btn btn-info">
                                    <i class="bi bi-eye me-2"></i>View Attendees
                                </a>
                                <a href="{{ route('scanner.select-event', ['event' => $event->id]) }}" 
                                   class="btn btn-primary">
                                    <i class="bi bi-upc-scan me-2"></i>Start Scanning
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