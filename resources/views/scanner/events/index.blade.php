<x-app-layout>
    <x-page-header 
        title="{{ auth()->user()->hasRole('admin') ? 'Available Events' : 'Your Assigned Events' }}"
        :breadcrumbs="[
            'Dashboard' => route('scanner.dashboard'),
            'Events' => null
        ]"
    >
        <x-slot name="actions">
            <div class="d-flex gap-2">
                <a href="{{ route('scanner.scan.index') }}" class="btn btn-primary">
                    <i class="bi bi-upc-scan me-2"></i>Start Scanning
                </a>
                <a href="{{ route('scanner.validate.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-key-fill me-2"></i>Manual Validation
            </a>
        </div>
    </x-slot>
    </x-page-header>

    <!-- Events Grid -->
    <div class="row g-4">
        @forelse($events as $event)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <span class="h5 mb-0 text-primary">
                                    {{ substr($event->name, 0, 1) }}
                                </span>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h5 class="card-title mb-1">{{ $event->name }}</h5>
                                <p class="text-muted small mb-0">
                                    Assigned {{ $event->pivot->assigned_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center text-muted mb-2">
                            <i class="bi bi-calendar-event me-2"></i>
                            {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                            </div>
                            <div class="d-flex align-items-center text-muted">
                            <i class="bi bi-geo-alt me-2"></i>
                            {{ $event->location }}
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="small text-muted">Total</div>
                                    <div class="h5 mb-0">{{ $event->scan_stats['total_attendees'] }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-success bg-opacity-10 rounded p-2 text-center">
                                    <div class="small text-success">Scanned</div>
                                    <div class="h5 mb-0 text-success">{{ $event->scan_stats['scanned_count'] }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-warning bg-opacity-10 rounded p-2 text-center">
                                    <div class="small text-warning">Pending</div>
                                    <div class="h5 mb-0 text-warning">{{ $event->scan_stats['pending_count'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <span class="badge rounded-pill 
                                @if($event->status === 'active') bg-success
                                @elseif($event->status === 'completed') bg-primary
                                @elseif($event->status === 'cancelled') bg-danger
                                @else bg-warning @endif">
                                {{ ucfirst($event->status) }}
                            </span>
                            <div class="btn-group">
                                <a href="{{ route('events.show', $event) }}" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <form action="{{ route('scanner.events.select', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-upc-scan me-1"></i>Scan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                        <h5 class="card-title">
                                @if(auth()->user()->hasRole('admin'))
                                    No Active Events
                                @else
                                    No Assigned Events
                                @endif
                            </h5>
                        <p class="text-muted mb-0">
                                @if(auth()->user()->hasRole('admin'))
                                    There are no active events available at the moment.
                                @else
                                    You have not been assigned to any events yet. Please contact your administrator.
                                @endif
                            </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($events->hasPages())
        <div class="mt-4">
        {{ $events->links() }}
    </div>
    @endif
</x-app-layout> 