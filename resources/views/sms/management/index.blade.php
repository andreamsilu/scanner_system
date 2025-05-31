<x-app-layout>
    <x-slot:title>
        SMS Management
    </x-slot>

    <x-page-header 
        title="SMS Management"
        :breadcrumbs="['SMS Management' => null]"
    />

    @if(isset($error))
        <div class="alert alert-danger">
            {{ $error }}
        </div>
    @endif

    <!-- SMS Stats Cards -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Total SMS Sent</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::count() }}</h5>
                            <small class="text-muted small">All time</small>
                        </div>
                        <div class="p-1 rounded-circle bg-info bg-opacity-10 text-info">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Provider Balance</h6>
                            @if(isset($balance))
                                <h5 class="card-title mb-0">
                                    {{ number_format($balance['balance'], 2) }} {{ $balance['currency'] }}
                                </h5>
                                <small class="text-muted small">{{ $provider }}</small>
                            @else
                                <h5 class="card-title mb-0">N/A</h5>
                                <small class="text-muted small">Unable to fetch balance</small>
                            @endif
                        </div>
                        <div class="p-1 rounded-circle bg-success bg-opacity-10 text-success">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Successful SMS</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::where('status', 'sent')->count() }}</h5>
                            <small class="text-muted small">Last 30 days</small>
                        </div>
                        <div class="p-1 rounded-circle bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border h-100 stat-card">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">Failed SMS</h6>
                            <h5 class="card-title mb-0">{{ \App\Models\SmsLog::where('status', 'failed')->count() }}</h5>
                            <small class="text-muted small">Last 30 days</small>
                        </div>
                        <div class="p-1 rounded-circle bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test SMS Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Send Test SMS</h5>
        </div>
        <div class="card-body">
            <form id="testSmsForm" class="row g-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required
                           placeholder="+1234567890">
                </div>
                <div class="col-md-6">
                    <label for="message" class="form-label">Message</label>
                    <input type="text" class="form-control" id="message" name="message" required
                           maxlength="160" placeholder="Enter your test message">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Send Test SMS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent SMS Logs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent SMS Logs</h5>
            <a href="{{ route('sms.management.logs') }}" class="btn btn-sm btn-outline-primary">
                View All Logs
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>To</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Provider</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(\App\Models\SmsLog::latest()->take(10)->get() as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td>{{ $log->to }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->type === 'invitation' ? 'primary' : ($log->type === 'reminder' ? 'warning' : 'info') }}">
                                        {{ ucfirst($log->type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ ucfirst($log->provider) }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                        {{ $log->message }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-chat-dots fs-4 d-block mb-2"></i>
                                        No SMS logs found
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.getElementById('testSmsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            
            const response = await fetch('{{ route("sms.management.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    phone: form.phone.value,
                    message: form.message.value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                toastr.success(result.message);
                form.reset();
                // Reload the page to update the logs
                window.location.reload();
            } else {
                toastr.error(result.message);
            }
        } catch (error) {
            toastr.error('Failed to send test SMS. Please try again.');
            console.error('Error:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
    </script>
    @endpush
</x-app-layout> 