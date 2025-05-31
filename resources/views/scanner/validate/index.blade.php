<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <a href="{{ route('scanner.dashboard') }}" class="btn btn-sm btn-link text-muted p-0 me-3">
                    <i class="bi bi-arrow-left fs-4"></i>
                </a>
                <h2 class="h4 mb-0">Manual Token Validation</h2>
            </div>
            @if($event)
                <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-0">
                    <i class="bi bi-calendar-event me-2"></i>
                    <div>
                        <strong class="d-block">{{ $event->name }}</strong>
                        <small>{{ $event->start_date->format('M d, Y h:i A') }}</small>
                    </div>
                    <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-muted p-0">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="row g-4">
        <!-- Validation Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Validate Ticket</h5>
                </div>
                <div class="card-body">
                    @if($event)
                        <form id="validateForm" action="{{ route('scanner.scan.index', ['event' => $event->id]) }}" method="GET" class="mb-4">
                            @csrf
                            <div class="mb-3">
                                <label for="token" class="form-label">Ticket Token</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control form-control-lg text-center" 
                                           id="token" 
                                           name="token"
                                           placeholder="Enter 7-digit token"
                                           pattern="[0-9]{7}"
                                           title="Please enter a 7-digit token"
                                           maxlength="7"
                                           autocomplete="off"
                                           required
                                           autofocus>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-upc-scan me-1"></i>Scan
                                    </button>
                                </div>
                                <div class="form-text">Enter the 7-digit ticket token to validate manually</div>
                            </div>
                        </form>

                        <!-- Validation Result -->
                        <div id="validationResult" class="alert d-none">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-1"></h5>
                                    <p class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-3 mb-0">Please select an event first</p>
                            <a href="{{ route('scanner.events.index') }}" class="btn btn-primary mt-3">
                                <i class="bi bi-calendar me-1"></i>Select Event
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Validations -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Recent Validations</h5>
                </div>
                <div class="card-body p-0">
                    @if($recent_validations->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recent_validations as $validation)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $validation['attendee'] }}</h6>
                                            <small class="text-muted d-block">{{ $validation['time'] }}</small>
                                            @if($validation['email'])
                                                <small class="text-muted d-block">{{ $validation['email'] }}</small>
                                            @endif
                                        </div>
                                        <span class="badge bg-{{ $validation['status_class'] }}">
                                            {{ ucfirst($validation['status']) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history text-muted" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-3 mb-0">No recent validations</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('validateForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const token = document.getElementById('token').value;
            if (token.length === 7 && /^\d+$/.test(token)) {
                // Store token in session storage for scan page
                sessionStorage.setItem('manual_token', token);
                // Redirect to scan page
                window.location.href = this.action;
            } else {
                // Show validation error
                const resultDiv = document.getElementById('validationResult');
                resultDiv.className = 'alert alert-danger d-block';
                resultDiv.querySelector('.bi').className = 'bi bi-exclamation-circle fs-4';
                resultDiv.querySelector('.alert-heading').textContent = 'Invalid Token';
                resultDiv.querySelector('p').textContent = 'Please enter a valid 7-digit token.';
            }
        });

        // Auto-focus input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const tokenInput = document.getElementById('token');
            if (tokenInput) {
                tokenInput.focus();
            }
        });
    </script>
    @endpush

    @push('styles')
    <style>
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            .list-group-item {
                padding: 0.75rem;
            }
            .form-control-lg {
                font-size: 1.25rem;
            }
            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 1.25rem;
            }
        }
        @media (max-width: 576px) {
            .card-header {
                padding: 0.75rem 1rem;
            }
            .card-body {
                padding: 0.75rem;
            }
            .form-control-lg {
                font-size: 1.125rem;
            }
            .btn-lg {
                padding: 0.375rem 0.75rem;
                font-size: 1.125rem;
            }
            .alert {
                padding: 0.75rem;
            }
        }
    </style>
    @endpush
</x-app-layout> 