<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h3 mb-0">Validate Tickets</h2>
            <a href="{{ route('scanner.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Event Selection -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Select Event</h5>
                    </div>
                    <div class="card-body">
                        @if(session('selected_event_id'))
                            @php
                                $selectedEvent = \App\Models\Event::find(session('selected_event_id'));
                            @endphp
                            @if($selectedEvent)
                                <div class="alert alert-success mb-3">
                                    <h6 class="alert-heading mb-1">Currently Selected Event</h6>
                                    <p class="mb-0">{{ $selectedEvent->name }}</p>
                                    <small class="d-block mt-1">
                                        {{ $selectedEvent->formatted_date_range }}
                                    </small>
                                </div>
                                <a href="{{ route('scanner.deselect-event', ['event' => $selectedEvent->id]) }}" 
                                   class="btn btn-outline-danger w-100">
                                    <i class="bi bi-x-circle me-2"></i>Deselect Event
                                </a>
                            @endif
                        @else
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Please select an event to start validating tickets
                            </div>
                        @endif

                        <div class="list-group mt-3">
                            @forelse($events as $event)
                                <a href="{{ route('scanner.select-event', ['event' => $event->id]) }}" 
                                   class="list-group-item list-group-item-action {{ session('selected_event_id') == $event->id ? 'active' : '' }}">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">{{ $event->name }}</h6>
                                        {!! $event->status_badge !!}
                                    </div>
                                    <small class="text-muted">
                                        {{ $event->formatted_date_range }}
                                    </small>
                                    <div class="mt-2">
                                        <small class="d-block">
                                            <i class="bi bi-people me-1"></i>
                                            {{ $event->attendees_count }} Attendees
                                        </small>
                                        <small class="d-block">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ $event->scanned_count ?? 0 }} Checked In
                                        </small>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x text-muted fs-1"></i>
                                    <p class="text-muted mt-2 mb-0">No events available</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Validation -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Validate Ticket</h5>
                    </div>
                    <div class="card-body">
                        @if(!session('selected_event_id'))
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Please select an event from the list to start validating tickets
                            </div>
                        @else
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if(session('warning'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('warning') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('scanner.validate-token') }}" method="POST" class="mb-4">
                                @csrf
                                <div class="mb-3">
                                    <label for="token" class="form-label">Attendee Token</label>
                                    <div class="input-group input-group-lg">
                                        <input type="text" 
                                               class="form-control @error('token') is-invalid @enderror" 
                                               id="token" 
                                               name="token" 
                                               placeholder="Enter attendee token"
                                               required
                                               pattern="[A-Za-z0-9]+"
                                               title="Please enter a valid token"
                                               autofocus>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search me-2"></i>Validate
                                        </button>
                                    </div>
                                    @error('token')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </form>

                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Instructions</h6>
                                    <ul class="mb-0">
                                        <li>Select an event from the list on the left</li>
                                        <li>Enter the attendee's token in the input field above</li>
                                        <li>Click "Validate" or press Enter to check the token</li>
                                        <li>The system will verify the token and update the attendee's status</li>
                                        <li>You'll receive immediate feedback on the validation result</li>
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Validations -->
                @if(session('selected_event_id'))
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Recent Validations</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Attendee</th>
                                            <th>Status</th>
                                            <th>Scanned By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recent_validations ?? [] as $validation)
                                            <tr>
                                                <td>{{ $validation['time'] }}</td>
                                                <td>
                                                    {{ $validation['attendee'] }}
                                                    @if(isset($validation['email']))
                                                        <br>
                                                        <small class="text-muted">{{ $validation['email'] }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $validation['status_class'] }}">
                                                        {{ ucfirst($validation['status']) }}
                                                    </span>
                                                </td>
                                                <td>{{ $validation['scanner'] ?? 'System' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="bi bi-inbox fa-2x mb-2"></i>
                                                        <p class="mb-0">No recent validations</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tokenInput = document.getElementById('token');
        if (tokenInput) {
            // Auto-uppercase input
            tokenInput.addEventListener('input', function(e) {
                this.value = this.value.toUpperCase();
            });

            // Focus input on page load
            tokenInput.focus();

            // Clear input after successful submission
            @if(session('success'))
                tokenInput.value = '';
                tokenInput.focus();
            @endif
        }
    });
    </script>
    @endpush
</x-app-layout> 