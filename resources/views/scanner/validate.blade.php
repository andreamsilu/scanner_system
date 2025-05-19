<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h3 mb-0">Validate Attendee Token</h2>
            <a href="{{ route('scanner.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Validate Attendee Token</h5>
                </div>
                <div class="card-body">
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
                                <li>Enter the attendee's token in the input field above</li>
                                <li>Click "Validate" or press Enter to check the token</li>
                                <li>The system will verify the token and update the attendee's status</li>
                                <li>You'll receive immediate feedback on the validation result</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Validations -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Validations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Attendee</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_validations ?? [] as $validation)
                                    <tr>
                                        <td>{{ $validation['time'] }}</td>
                                        <td>{{ $validation['attendee'] }}</td>
                                        <td>{{ $validation['event'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $validation['status_class'] }}">
                                                {{ ucfirst($validation['status']) }}
                                            </span>
                                        </td>
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