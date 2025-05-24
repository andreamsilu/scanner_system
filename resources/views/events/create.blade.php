<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Create New Event</h2>
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Events
            </a>
        </div>
    </x-slot>

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
            <form action="{{ route('events.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name" class="form-label">Event Name <span class="text-danger">*</span></label>
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
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control datepicker @error('start_date') is-invalid @enderror" 
                                   id="start_date" 
                                   name="start_date" 
                                   value="{{ old('start_date') }}" 
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
                                   value="{{ old('end_date') }}" 
                                   placeholder="DD/MM/YYYY HH:mm"
                                   required>
                            @error('end_date')
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
                                   value="{{ old('venue') }}" 
                                   required>
                            @error('venue')
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
                                   value="{{ old('capacity') }}" 
                                   min="1" 
                                   required>
                            @error('capacity')
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
                                   value="{{ old('location') }}" 
                                   required>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Event
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
                minDate: "today",
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