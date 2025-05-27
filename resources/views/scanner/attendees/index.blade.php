<x-app-layout>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Event Attendees</h5>
                                <p class="text-muted mb-0">View and manage attendees for your assigned events</p>
                            </div>
                            <div class="col-auto">
                                <form action="{{ route('scanner.attendees.index') }}" method="GET" class="d-flex gap-2">
                                    <div class="input-group">
                                        <input type="text" name="event_search" class="form-control" placeholder="Search events..." value="{{ request('event_search') }}">
                                        <input type="text" name="search" class="form-control" placeholder="Search attendees..." value="{{ request('search') }}">
                                        <select name="status" class="form-select" style="max-width: 150px;">
                                            <option value="">All Status</option>
                                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Used</option>
                                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        @if(request()->hasAny(['event_search', 'search', 'status']))
                                            <a href="{{ route('scanner.attendees.index') }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-lg"></i> Clear
                                            </a>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if($events->isEmpty())
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <p class="mt-3 text-muted">No events found.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0">Event</th>
                                            <th class="border-0">Date</th>
                                            <th class="border-0 text-center">Total Attendees</th>
                                            <th class="border-0 text-center">Scanned</th>
                                            <th class="border-0 text-center">Pending</th>
                                            <th class="border-0 text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($events as $event)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            <h6 class="mb-0">{{ $event->name }}</h6>
                                                            <small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span>{{ $event->start_date->format('M d, Y') }}</span>
                                                        <small class="text-muted">{{ $event->start_date->format('h:i A') }} - {{ $event->end_date->format('h:i A') }}</small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary rounded-pill">{{ $event->attendees_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success rounded-pill">{{ $event->scanned_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning rounded-pill">{{ $event->pending_count }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('scanner.events.attendees', $event) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-people"></i> View Attendees
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                {{ $events->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .table > :not(caption) > * > * {
            padding: 1rem;
        }
        .badge {
            font-size: 0.875rem;
            padding: 0.5em 0.75em;
        }
    </style>
    @endpush
</x-app-layout> 