<x-app-layout>
    <x-page-header 
        title="Edit Event"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Edit Event' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Event
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <form action="{{ route('events.update', $event) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PATCH')
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name" class="form-label">Event Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $event->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="venue" class="form-label">Venue <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('venue') is-invalid @enderror" 
                                   id="venue" 
                                   name="venue" 
                                   value="{{ old('venue', $event->venue) }}" 
                                   required>
                            @error('venue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('location') is-invalid @enderror" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location', $event->location) }}" 
                                   required>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control datepicker @error('start_date') is-invalid @enderror" 
                                   id="start_date" 
                                   name="start_date" 
                                   value="{{ old('start_date', $event->start_date->format('d/m/Y H:i')) }}" 
                                   placeholder="DD/MM/YYYY HH:mm"
                                   required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control datepicker @error('end_date') is-invalid @enderror" 
                                   id="end_date" 
                                   name="end_date" 
                                   value="{{ old('end_date', $event->end_date->format('d/m/Y H:i')) }}" 
                                   placeholder="DD/MM/YYYY HH:mm"
                                   required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" 
                                   name="capacity" 
                                   value="{{ old('capacity', $event->capacity) }}" 
                                   min="1" 
                                   required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- RSVP Section -->
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-telephone me-2"></i>RSVP Configuration (Optional)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="rsvp_enabled" name="rsvp_enabled" value="1" {{ old('rsvp_enabled', $event->rsvp_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="rsvp_enabled">
                                                Enable RSVP for this event
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="rsvp_option_1_label" class="form-label">RSVP Option 1 Label</label>
                                        <input type="text" 
                                               class="form-control @error('rsvp_option_1_label') is-invalid @enderror" 
                                               id="rsvp_option_1_label" 
                                               name="rsvp_option_1_label" 
                                               value="{{ old('rsvp_option_1_label', $event->rsvp_option_1_label) }}" 
                                               placeholder="e.g., Accept Invitation">
                                        @error('rsvp_option_1_label')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="rsvp_option_1_phone" class="form-label">RSVP Option 1 Phone</label>
                                        <input type="text" 
                                               class="form-control @error('rsvp_option_1_phone') is-invalid @enderror" 
                                               id="rsvp_option_1_phone" 
                                               name="rsvp_option_1_phone" 
                                               value="{{ old('rsvp_option_1_phone', $event->rsvp_option_1_phone) }}" 
                                               placeholder="e.g., +255 712 345 678">
                                        @error('rsvp_option_1_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="rsvp_option_2_label" class="form-label">RSVP Option 2 Label</label>
                                        <input type="text" 
                                               class="form-control @error('rsvp_option_2_label') is-invalid @enderror" 
                                               id="rsvp_option_2_label" 
                                               name="rsvp_option_2_label" 
                                               value="{{ old('rsvp_option_2_label', $event->rsvp_option_2_label) }}" 
                                               placeholder="e.g., Decline Invitation">
                                        @error('rsvp_option_2_label')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="rsvp_option_2_phone" class="form-label">RSVP Option 2 Phone</label>
                                        <input type="text" 
                                               class="form-control @error('rsvp_option_2_phone') is-invalid @enderror" 
                                               id="rsvp_option_2_phone" 
                                               name="rsvp_option_2_phone" 
                                               value="{{ old('rsvp_option_2_phone', $event->rsvp_option_2_phone) }}" 
                                               placeholder="e.g., +255 712 345 679">
                                        @error('rsvp_option_2_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="rsvp_message" class="form-label">RSVP Message</label>
                                        <textarea class="form-control @error('rsvp_message') is-invalid @enderror" 
                                                  id="rsvp_message" 
                                                  name="rsvp_message" 
                                                  rows="3" 
                                                  placeholder="Optional message to include with RSVP options">{{ old('rsvp_message', $event->rsvp_message) }}</textarea>
                                        @error('rsvp_message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">This message will be included in SMS invitations and event cards.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Update Event
                    </button>
                    <a href="{{ route('events.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <!-- Include Flatpickr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Initialize date pickers
        document.addEventListener('DOMContentLoaded', function() {
            const datePickerConfig = {
                enableTime: true,
                dateFormat: "d/m/Y H:i",
                time_24hr: true,
                locale: {
                    firstDayOfWeek: 1
                }
            };

            // Initialize start date picker
            const startDatePicker = flatpickr("#start_date", {
                ...datePickerConfig,
                onChange: function(selectedDates, dateStr) {
                    // Update end date min date when start date changes
                    endDatePicker.set("minDate", selectedDates[0]);
                }
            });

            // Initialize end date picker
            const endDatePicker = flatpickr("#end_date", {
                ...datePickerConfig,
                onChange: function(selectedDates, dateStr) {
                    // Validate end date is after start date
                    const startDate = startDatePicker.selectedDates[0];
                    if (startDate && selectedDates[0] <= startDate) {
                        endDatePicker.setDate(startDate);
                        alert("End date must be after start date");
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 