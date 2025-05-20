<x-app-layout>
    <x-slot name="header">
        <div class="py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Edit Attendee</h2>
                    <nav aria-label="breadcrumb" class="mt-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('attendees.index') }}">Attendees</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('attendees.show', $attendee) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-2"></i>View Attendee
                    </a>
                    <a href="{{ route('attendees.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Attendees
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil me-2"></i>Edit Attendee Information
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendees.update', $attendee) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $attendee->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $attendee->email) }}" 
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $attendee->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="event_id" class="form-label">Event</label>
                                    <select class="form-select @error('event_id') is-invalid @enderror" 
                                            id="event_id" 
                                            name="event_id" 
                                            required>
                                        <option value="">Select Event</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event->id }}" 
                                                    {{ old('event_id', $attendee->event_id) == $event->id ? 'selected' : '' }}>
                                                {{ $event->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('event_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_status" class="form-label">Registration Status</label>
                                    <select class="form-select @error('registration_status') is-invalid @enderror" 
                                            id="registration_status" 
                                            name="registration_status" 
                                            required>
                                        <option value="registered" 
                                                {{ old('registration_status', $attendee->registration_status) === 'registered' ? 'selected' : '' }}>
                                            Registered
                                        </option>
                                        <option value="checked_in" 
                                                {{ old('registration_status', $attendee->registration_status) === 'checked_in' ? 'selected' : '' }}>
                                            Checked In
                                        </option>
                                        <option value="cancelled" 
                                                {{ old('registration_status', $attendee->registration_status) === 'cancelled' ? 'selected' : '' }}>
                                            Cancelled
                                        </option>
                                    </select>
                                    @error('registration_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="3">{{ old('notes', $attendee->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check mb-3">
                                    <input type="checkbox" 
                                           class="form-check-input @error('requires_ticket') is-invalid @enderror" 
                                           id="requires_ticket" 
                                           name="requires_ticket" 
                                           value="1" 
                                           {{ old('requires_ticket', $attendee->ticket ? '1' : '') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_ticket">
                                        Generate Ticket for this Attendee
                                    </label>
                                    @error('requires_ticket')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Update Attendee
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize phone input with international format
        $(document).ready(function() {
            $('#phone').on('input', function() {
                // Remove any non-digit characters
                let value = $(this).val().replace(/\D/g, '');
                
                // Format the phone number
                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = '(' + value;
                    } else if (value.length <= 6) {
                        value = '(' + value.substring(0, 3) + ') ' + value.substring(3);
                    } else {
                        value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
                    }
                }
                
                $(this).val(value);
            });

            // Form validation
            $('form').on('submit', function(e) {
                let isValid = true;
                const email = $('#email').val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!emailRegex.test(email)) {
                    $('#email').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#email').removeClass('is-invalid');
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 