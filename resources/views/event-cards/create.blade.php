@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Create Event Card</h5>
                        <a href="{{ route('event-cards.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cards
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(!$selectedEvent)
                        <!-- Event Selection Form -->
                        <form action="{{ route('event-cards.create') }}" method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="event_id" class="form-label">Select Event</label>
                                        <select name="event_id" id="event_id" class="form-select" required>
                                            <option value="">Choose an event...</option>
                                            @forelse($events as $event)
                                                <option value="{{ $event->id }}">
                                                    {{ $event->name }} 
                                                    ({{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }})
                                                </option>
                                            @empty
                                                <option value="" disabled>No active events available</option>
                                            @endforelse
                                        </select>
                                        @if($events->isEmpty())
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No active events are currently available. Please create an event first.
                                                <a href="{{ route('events.create') }}" class="btn btn-primary btn-sm ms-3">
                                                    <i class="fas fa-plus me-2"></i>Create Event
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary" {{ $events->isEmpty() ? 'disabled' : '' }}>
                                        <i class="fas fa-arrow-right me-2"></i>Continue
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <!-- Card Creation Form -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="mb-2">Selected Event: {{ $selectedEvent->name }}</h6>
                                    <p class="mb-0">
                                        <strong>Date:</strong> {{ $selectedEvent->start_date->format('M d, Y') }} - {{ $selectedEvent->end_date->format('M d, Y') }}<br>
                                        <strong>Location:</strong> {{ $selectedEvent->location }}<br>
                                        <strong>Venue:</strong> {{ $selectedEvent->venue }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('event-cards.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="attendee_id" class="form-label">Select Attendee</label>
                                        <select name="attendee_id" id="attendee_id" class="form-select @error('attendee_id') is-invalid @enderror" required>
                                            <option value="">Choose an attendee...</option>
                                            @forelse($selectedEvent->attendees as $attendee)
                                                <option value="{{ $attendee->id }}" {{ old('attendee_id') == $attendee->id ? 'selected' : '' }}>
                                                    {{ $attendee->name }} ({{ $attendee->email }}) - {{ ucfirst($attendee->ticket_type) }}
                                                </option>
                                            @empty
                                                <option value="" disabled>No attendees available for this event</option>
                                            @endforelse
                                        </select>
                                        @error('attendee_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($selectedEvent->attendees->isEmpty())
                                            <div class="alert alert-warning mt-3">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No attendees are registered for this event. Please add attendees first.
                                                <a href="{{ route('attendees.create') }}" class="btn btn-primary btn-sm ms-3">
                                                    <i class="fas fa-plus me-2"></i>Add Attendee
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary" {{ $selectedEvent->attendees->isEmpty() ? 'disabled' : '' }}>
                                        <i class="fas fa-save me-2"></i>Create Card
                                    </button>
                                    <a href="{{ route('event-cards.create') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize select2 for better dropdown experience
        $('#event_id, #attendee_id, #type, #status').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
</script>
@endpush
@endsection 