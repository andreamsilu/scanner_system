<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">{{ $event->name }} - Attendees</h2>
                <p class="text-muted mb-0">View and manage event attendees</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('scanner.events.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Events
                </a>
                <form action="{{ route('scanner.events.select', ['event' => $event->id]) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upc-scan me-2"></i>Start Scanning
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('scanner.events.attendees', $event) }}" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Search by name or email...">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Ticket Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Used</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendees List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Ticket Status</th>
                                <th>Ticket Type</th>
                                <th>Last Scan</th>
                                <th>Registered</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendees as $attendee)
                                <tr>
                                    <td>{{ $attendee->name }}</td>
                                    <td>{{ $attendee->email }}</td>
                                    <td>{{ $attendee->phone ?? 'N/A' }}</td>
                                    <td>
                                        @if($attendee->ticket)
                                            <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'active' ? 'primary' : 'danger') }}">
                                                {{ ucfirst($attendee->ticket->status) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No Ticket</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendee->ticket)
                                            <span class="badge bg-info text-dark">{{ ucfirst($attendee->ticket->type) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendee->ticket && $attendee->ticket->scanLogs->isNotEmpty())
                                            <span class="text-muted" title="{{ $attendee->ticket->scanLogs->first()->created_at->format('d-m-Y H:i') }}">
                                                {{ $attendee->ticket->scanLogs->first()->created_at->diffForHumans() }}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                by {{ $attendee->ticket->scanLogs->first()->scanner->name }}
                                            </small>
                                        @else
                                            <span class="text-muted">Not scanned</span>
                                        @endif
                                    </td>
                                    <td>{{ $attendee->created_at->format('d-m-Y') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewAttendeeModal{{ $attendee->id }}"
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>

                                        <!-- View Attendee Modal -->
                                        <div class="modal fade" id="viewAttendeeModal{{ $attendee->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Attendee Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Personal Information</h6>
                                                                <dl class="row mb-0">
                                                                    <dt class="col-sm-4">Name</dt>
                                                                    <dd class="col-sm-8">{{ $attendee->name }}</dd>
                                                                    
                                                                    <dt class="col-sm-4">Email</dt>
                                                                    <dd class="col-sm-8">{{ $attendee->email }}</dd>
                                                                    
                                                                    <dt class="col-sm-4">Phone</dt>
                                                                    <dd class="col-sm-8">{{ $attendee->phone ?? 'N/A' }}</dd>
                                                                    
                                                                    <dt class="col-sm-4">Position</dt>
                                                                    <dd class="col-sm-8">{{ $attendee->position ?? 'N/A' }}</dd>
                                                                </dl>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="mb-3">Ticket Information</h6>
                                                                <dl class="row mb-0">
                                                                    <dt class="col-sm-4">Status</dt>
                                                                    <dd class="col-sm-8">
                                                                        @if($attendee->ticket)
                                                                            <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'active' ? 'primary' : 'danger') }}">
                                                                                {{ ucfirst($attendee->ticket->status) }}
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-secondary">No Ticket</span>
                                                                        @endif
                                                                    </dd>
                                                                    
                                                                    <dt class="col-sm-4">Token</dt>
                                                                    <dd class="col-sm-8">
                                                                        @if($attendee->ticket)
                                                                            <code>{{ $attendee->ticket->token }}</code>
                                                                        @else
                                                                            N/A
                                                                        @endif
                                                                    </dd>
                                                                    
                                                                    <dt class="col-sm-4">Registered</dt>
                                                                    <dd class="col-sm-8">{{ $attendee->created_at->format('M d, Y h:i A') }}</dd>
                                                                </dl>
                                                            </div>
                                                        </div>

                                                        @if($attendee->ticket && $attendee->ticket->scanLogs->isNotEmpty())
                                                            <hr>
                                                            <h6 class="mb-3">Scan History</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Time</th>
                                                                            <th>Scanner</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($attendee->ticket->scanLogs as $scan)
                                                                            <tr>
                                                                                <td>{{ $scan->created_at->format('M d, Y h:i A') }}</td>
                                                                                <td>{{ $scan->scanner->name }}</td>
                                                                                <td>
                                                                                    <span class="badge bg-{{ $scan->status === 'success' ? 'success' : 'danger' }}">
                                                                                        {{ ucfirst($scan->status) }}
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-people fa-3x mb-3"></i>
                                            <p class="mb-0">No attendees found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $attendees->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 