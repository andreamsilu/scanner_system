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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="card-subtitle mb-0 text-muted small">Provider Balance</h6>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted refresh-balance" title="Refresh Balance">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div class="balance-display">
                                @if(isset($balance) && $balance['success'])
                                    <h5 class="card-title mb-0">
                                        {{ number_format($balance['units']) }} <small class="text-muted">SMS Units</small>
                                    </h5>
                                    <div class="d-flex flex-column">
                                        <small class="text-muted small">{{ $provider }}</small>
                                        <span class="badge bg-{{ $balance['status'] === 'critical' ? 'danger' : ($balance['status'] === 'low' ? 'warning' : 'success') }} mt-1">
                                            {{ ucfirst($balance['status']) }}
                                        </span>
                                        @if(isset($balance['last_updated']))
                                            <small class="text-muted small mt-1">
                                                Updated: {{ \Carbon\Carbon::parse($balance['last_updated'])->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
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

    $(document).ready(function() {
        // Handle balance refresh
        $('.refresh-balance').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const card = button.closest('.stat-card');
            
            // Disable button and show loading state
            button.prop('disabled', true).html('<i class="bi bi-arrow-clockwise spin"></i>');
            
            // Make AJAX request to refresh balance
            $.get('{{ route("sms.management.refresh-balance") }}', function(response) {
                if (response.success) {
                    // Update balance display
                    const balance = response.balance;
                    card.find('.card-title').text(
                        number_format(balance.units) + ' <small class="text-muted">SMS Units</small>'
                    );
                    
                    // Update status badge
                    const statusBadge = card.find('.badge');
                    if (balance.status) {
                        if (statusBadge.length) {
                            statusBadge
                                .removeClass('bg-success bg-warning')
                                .addClass(balance.status === 'active' ? 'bg-success' : 'bg-warning')
                                .text(balance.status.charAt(0).toUpperCase() + balance.status.slice(1));
                        } else {
                            card.find('.d-flex.flex-column').append(`
                                <span class="badge bg-${balance.status === 'active' ? 'success' : 'warning'} mt-1">
                                    ${balance.status.charAt(0).toUpperCase() + balance.status.slice(1)}
                                </span>
                            `);
                        }
                    }
                    
                    // Update last updated time
                    if (balance.last_updated) {
                        const lastUpdated = card.find('.text-muted.small:last');
                        if (lastUpdated.length) {
                            lastUpdated.text('Updated: ' + moment(balance.last_updated).fromNow());
                        } else {
                            card.find('.d-flex.flex-column').append(`
                                <small class="text-muted small mt-1">
                                    Updated: ${moment(balance.last_updated).fromNow()}
                                </small>
                            `);
                        }
                    }
                    
                    // Show success toast
                    toastr.success('Balance updated successfully');
                } else {
                    toastr.error(response.message || 'Failed to update balance');
                }
            }).fail(function() {
                toastr.error('Failed to update balance. Please try again.');
            }).always(function() {
                // Re-enable button and restore icon
                button.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i>');
            });
        });
    });

    // Add CSS for spinning animation
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                100% { transform: rotate(360deg); }
            }
        </style>
    `);
    </script>
    @endpush
</x-app-layout> 