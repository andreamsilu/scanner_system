<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 mb-0">Scanner Details</h2>
                <p class="text-muted mb-0">{{ $scanner->name }}</p>
            </div>
            <div>
                <a href="{{ route('scanners.edit', $scanner) }}" class="btn btn-primary me-2">
                    <i class="bi bi-pencil me-2"></i>Edit Scanner
                </a>
                <a href="{{ route('scanners.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <!-- Scanner Information -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-person-circle fs-1"></i>
                        </div>
                        <h4 class="mb-1">{{ $scanner->name }}</h4>
                        <p class="text-muted mb-2">{{ $scanner->email }}</p>
                        <span class="badge bg-{{ $scanner->is_active ? 'success' : 'danger' }}">
                            {{ $scanner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <hr>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $scanner->is_active ? 'success' : 'danger' }}">
                                {{ $scanner->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Last Login:</dt>
                        <dd class="col-sm-8">
                            @if($scanner->last_login_at)
                                {{ $scanner->last_login_at->diffForHumans() }}
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Created:</dt>
                        <dd class="col-sm-8">{{ $scanner->created_at->format('M d, Y') }}</dd>

                        <dt class="col-sm-4">Assigned Events:</dt>
                        <dd class="col-sm-8">{{ $scanner->events->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-md-8 mb-4">
            <div class="row">
                <div class="col-sm-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-upc-scan"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Total Scans</h6>
                                    <h3 class="mb-0">{{ $stats['total_scans'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Today's Scans</h6>
                                    <h3 class="mb-0">{{ $stats['today_scans'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Valid Scans</h6>
                                    <h3 class="mb-0">{{ $stats['valid_scans'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Invalid Scans</h6>
                                    <h3 class="mb-0">{{ $stats['invalid_scans'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Events -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Assigned Events</h5>
                </div>
                <div class="card-body">
                    @if($scanner->events->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No active event assignments</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    @foreach($scanner->events as $event)
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
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Recent Scan Activity</h5>
                </div>
                <div class="card-body">
                    @if($recent_activity->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No recent scan activity</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Event</th>
                                        <th>Attendee</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_activity as $scan)
                                        <tr>
                                            <td>{{ $scan->created_at->format('M d, Y h:i A') }}</td>
                                            <td>{{ $scan->ticket->event->name }}</td>
                                            <td>
                                                {{ $scan->ticket->attendee->name }}
                                                <br>
                                                <small class="text-muted">{{ $scan->ticket->attendee->email }}</small>
                                            </td>
                                            <td>
                                                @switch($scan->status)
                                                    @case('success')
                                                        <span class="badge bg-success">Valid</span>
                                                        @break
                                                    @case('invalid')
                                                        <span class="badge bg-danger">Invalid</span>
                                                        @break
                                                    @case('duplicate')
                                                        <span class="badge bg-warning">Duplicate</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($scan->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-link btn-sm p-0" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#scanDetailsModal{{ $scan->id }}">
                                                    View Details
                                                </button>

                                                <!-- Scan Details Modal -->
                                                <div class="modal fade" 
                                                     id="scanDetailsModal{{ $scan->id }}" 
                                                     tabindex="-1" 
                                                     aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Scan Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <dl class="row mb-0">
                                                                    <dt class="col-sm-4">Scan Time:</dt>
                                                                    <dd class="col-sm-8">{{ $scan->created_at->format('M d, Y h:i:s A') }}</dd>

                                                                    <dt class="col-sm-4">Event:</dt>
                                                                    <dd class="col-sm-8">{{ $scan->ticket->event->name }}</dd>

                                                                    <dt class="col-sm-4">Attendee:</dt>
                                                                    <dd class="col-sm-8">
                                                                        {{ $scan->ticket->attendee->name }}
                                                                        <br>
                                                                        <small class="text-muted">{{ $scan->ticket->attendee->email }}</small>
                                                                    </dd>

                                                                    <dt class="col-sm-4">Status:</dt>
                                                                    <dd class="col-sm-8">
                                                                        @switch($scan->status)
                                                                            @case('success')
                                                                                <span class="badge bg-success">Valid</span>
                                                                                @break
                                                                            @case('invalid')
                                                                                <span class="badge bg-danger">Invalid</span>
                                                                                @break
                                                                            @case('duplicate')
                                                                                <span class="badge bg-warning">Duplicate</span>
                                                                                @break
                                                                            @default
                                                                                <span class="badge bg-secondary">{{ ucfirst($scan->status) }}</span>
                                                                        @endswitch
                                                                    </dd>

                                                                    <dt class="col-sm-4">Notes:</dt>
                                                                    <dd class="col-sm-8">{{ $scan->notes ?: 'N/A' }}</dd>
                                                                </dl>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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
</x-app-layout>

@push('styles')
<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush 