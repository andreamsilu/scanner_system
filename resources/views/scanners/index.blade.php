<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">Scanner Management</h2>
                <p class="text-muted mb-0">Manage scanners and their event assignments</p>
            </div>
            <a href="{{ route('scanners.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Add New Scanner
            </a>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($scanners->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No scanners found</p>
                            <a href="{{ route('scanners.create') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-person-plus me-2"></i>Add New Scanner
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Assigned Events</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scanners as $scanner)
                                        <tr>
                                            <td>{{ $scanner->name }}</td>
                                            <td>{{ $scanner->email }}</td>
                                            <td>
                                                @if($scanner->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($scanner->assignedEvents->isEmpty())
                                                    <span class="text-muted">No active assignments</span>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-link btn-sm p-0" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#scannerEventsModal{{ $scanner->id }}">
                                                        {{ $scanner->assignedEvents->count() }} Active Events
                                                    </button>
                                                @endif
                                            </td>
                                            <td>
                                                @if($scanner->last_login_at)
                                                    {{ $scanner->last_login_at->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('scanners.show', $scanner) }}" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('scanners.edit', $scanner) }}" 
                                                       class="btn btn-info btn-sm">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('scanners.destroy', $scanner) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this scanner?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Scanner Events Modal -->
                                        <div class="modal fade" 
                                             id="scannerEventsModal{{ $scanner->id }}" 
                                             tabindex="-1" 
                                             aria-labelledby="scannerEventsModalLabel{{ $scanner->id }}" 
                                             aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="scannerEventsModalLabel{{ $scanner->id }}">
                                                            Assigned Events for {{ $scanner->name }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="table-responsive">
                                                            <table class="table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Event Name</th>
                                                                        <th>Date</th>
                                                                        <th>Location</th>
                                                                        <th>Assigned At</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($scanner->assignedEvents as $event)
                                                                        <tr>
                                                                            <td>{{ $event->name }}</td>
                                                                            <td>
                                                                                {{ $event->start_date->format('M d, Y') }}
                                                                                <br>
                                                                                <small class="text-muted">
                                                                                    {{ $event->start_date->format('h:i A') }} - 
                                                                                    {{ $event->end_date->format('h:i A') }}
                                                                                </small>
                                                                            </td>
                                                                            <td>{{ $event->location }}</td>
                                                                            <td>
                                                                                @if($event->pivot->assigned_at)
                                                                                    {{ $event->pivot->assigned_at->format('M d, Y h:i A') }}
                                                                                @else
                                                                                    N/A
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                <a href="{{ route('events.show', $event) }}" 
                                                                                   class="btn btn-primary btn-sm">
                                                                                    <i class="bi bi-eye"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            {{ $scanners->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 