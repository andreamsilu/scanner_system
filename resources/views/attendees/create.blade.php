<x-app-layout>
    <x-page-header 
        title="Add New Attendee"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Attendees' => route('events.attendees.index', $event),
            'Add New' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Attendees
            </a>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-plus me-2"></i>Attendee Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('events.attendees.store', $event) }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" 
                                               class="form-control @error('name') is-invalid @enderror" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name') }}" 
                                               required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email <span class="text-muted">(Optional)</span></label>
                                        <input type="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               id="email" 
                                               name="email" 
                                               value="{{ old('email') }}"
                                               placeholder="Enter email (optional)"
                                        >
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Email is optional but must be unique if provided.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="position" class="form-label">Position</label>
                                        <input type="text" 
                                               class="form-control @error('position') is-invalid @enderror" 
                                               id="position" 
                                               name="position" 
                                               value="{{ old('position') }}">
                                        @error('position')
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
                                                  rows="3">{{ old('notes') }}</textarea>
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
                                               {{ old('requires_ticket') ? 'checked' : '' }}>
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
                                        <i class="bi bi-person-plus me-2"></i>Add Attendee
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
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