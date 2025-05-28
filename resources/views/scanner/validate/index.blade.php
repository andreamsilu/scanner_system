<x-app-layout>
    <x-page-header 
        title="Manual Token Validation"
        :breadcrumbs="[
            'Dashboard' => route('scanner.dashboard'),
            'Manual Token Validation' => null
        ]"
    >
        @if(session('selected_event_id'))
            <x-slot name="actions">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">
                        Selected Event: {{ $event->name }}
                    </span>
                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link text-danger p-0">Deselect Event</button>
                    </form>
                </div>
            </x-slot>
        @endif
    </x-page-header>

    <!-- Page Content -->
    <div class="row g-4">
        <!-- Token Validation Form -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form id="validateForm" class="mb-0">
                        <div class="mb-3">
                            <label for="token" class="form-label">Token</label>
                            <input type="text" class="form-control" id="token" name="token" 
                                   placeholder="Enter attendee token" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>Validate Token
                        </button>
                    </form>
                    <div id="validationResult" class="mt-3 d-none">
                        <!-- Validation result will be shown here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Validations -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Validations</h5>
                    <a href="{{ route('scanner.history') }}" class="btn btn-link btn-sm p-0">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($recent_validations as $validation)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $validation['attendee'] }}</h6>
                                        <small class="text-muted">{{ $validation['time'] }}</small>
                                        <small class="d-block text-muted">Scanner: {{ $validation['scanner'] }}</small>
                                    </div>
                                    <span class="badge rounded-pill 
                                        @if($validation['status_class'] === 'success') bg-success
                                        @elseif($validation['status_class'] === 'warning') bg-warning
                                        @else bg-danger @endif">
                                        {{ ucfirst($validation['status']) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="bi bi-key-fill display-4 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent validations</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!session('selected_event_id'))
        <div class="card mt-4">
            <div class="card-body text-center">
                <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                <h5 class="card-title">No Event Selected</h5>
                <p class="card-text text-muted">Please select an event to start validating tickets</p>
                <a href="{{ route('scanner.events.index') }}" class="btn btn-primary">
                    <i class="bi bi-calendar-event me-2"></i>Select Event
                </a>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        document.getElementById('validateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const token = document.getElementById('token').value;
            const resultDiv = document.getElementById('validationResult');
            
            // Show loading state
            resultDiv.classList.remove('d-none');
            resultDiv.innerHTML = `
                <div class="alert alert-info d-flex align-items-center mb-0">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Validating...</div>
                </div>
            `;

            // Send validation request
            fetch('{{ route("scanner.validate.token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success d-flex align-items-center mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Validation Successful</h6>
                                <p class="mb-0">Attendee: ${data.attendee.name}</p>
                                <p class="mb-0">Email: ${data.attendee.email}</p>
                                <p class="mb-0">Validated at: ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>
                            </div>
                        </div>
                    `;
                    
                    // Play success sound
                    const audio = new Audio('/sounds/success.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger d-flex align-items-center mb-0">
                            <i class="bi bi-x-circle me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Validation Failed</h6>
                                <p class="mb-0">${data.message}</p>
                            </div>
                        </div>
                    `;
                    
                    // Play error sound
                    const audio = new Audio('/sounds/error.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                }

                // Clear the input
                document.getElementById('token').value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center mb-0">
                        <i class="bi bi-x-circle me-2"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error</h6>
                            <p class="mb-0">An error occurred while validating the token. Please try again.</p>
                        </div>
                    </div>
                `;
            });
        });
    </script>
    @endpush
</x-app-layout> 