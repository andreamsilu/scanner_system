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
                            <h6 class="card-subtitle mb-0 text-muted small">SMS Credits</h6>
                            @if(isset($balance))
                                <h5 class="card-title mb-0">
                                    {{ number_format($balance['credits']) }} credits
                                </h5>
                                @if(isset($balance['units']) && $balance['units'] > 0)
                                    <small class="text-muted small">{{ number_format($balance['units']) }} units</small>
                                @else
                                    <small class="text-muted small">{{ ucfirst($provider) }}</small>
                                @endif
                            @else
                                <h5 class="card-title mb-0">N/A</h5>
                                <small class="text-muted small">Unable to fetch credits</small>
                            @endif
                        </div>
                        <div class="p-1 rounded-circle bg-success bg-opacity-10 text-success">
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

    <!-- Event Selection and Invitation SMS -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Send Invitation SMS to Event Attendees</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="eventSelect" class="form-label">Select Event</label>
                    <select class="form-select" id="eventSelect" required>
                        <option value="">Choose an event...</option>
                        @foreach(\App\Models\Event::orderBy('name')->get() as $event)
                            <option value="{{ $event->id }}" 
                                    data-attendees-count="{{ $event->attendees()->count() }}"
                                    data-attendees-with-phone="{{ $event->attendees()->whereNotNull('phone')->count() }}"
                                    data-already-sent="{{ $event->attendees()->whereHas('smsLogs', function($query) { $query->whereJsonContains('metadata->type', 'invitation'); })->count() }}">
                                {{ $event->name }} ({{ $event->date ? \Carbon\Carbon::parse($event->date)->format('M d, Y') : 'No date' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Event Statistics</label>
                    <div id="eventStats" class="text-muted small">
                        Select an event to see statistics
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-success" id="sendInvitationSmsBtn" disabled>
                        <i class="bi bi-envelope-paper me-2"></i>Send Invitation SMS
                    </button>
                    <small class="text-muted d-block mt-2">
                        This will send invitation SMS to all attendees who haven't received invitations yet.
                    </small>
                </div>
            </div>
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
                            <th>Time</th>
                            <th>Recipient</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                <td>{{ $log->recipient }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $log->message }}">
                                        {{ $log->message }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->getStatusBadgeClass() }}">
                                        {{ $log->getStatusMessage() }}
                                    </span>
                                    @if($log->provider_status && $log->provider_status !== $log->status)
                                        <small class="text-muted d-block">
                                            Provider: {{ ucfirst($log->provider_status) }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->delivered_at)
                                        <small class="text-muted d-block">
                                            Delivered: {{ $log->delivered_at->format('M d, Y H:i:s') }}
                                        </small>
                                    @endif
                                    @if($log->last_status_check)
                                        <small class="text-muted d-block">
                                            Last Check: {{ $log->last_status_check->diffForHumans() }}
                                        </small>
                                    @endif
                                    @if($log->reference)
                                        <small class="text-muted d-block">
                                            Ref: {{ $log->reference }}
                                        </small>
                                    @endif
                                    @if($log->error)
                                        <small class="text-danger d-block">
                                            {{ $log->error }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No SMS logs found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Event Selection and Invitation SMS functionality
    document.getElementById('eventSelect').addEventListener('change', function() {
        const eventSelect = this;
        const sendBtn = document.getElementById('sendInvitationSmsBtn');
        const statsDiv = document.getElementById('eventStats');
        
        if (eventSelect.value) {
            const selectedOption = eventSelect.options[eventSelect.selectedIndex];
            const attendeesCount = parseInt(selectedOption.dataset.attendeesCount);
            const attendeesWithPhone = parseInt(selectedOption.dataset.attendeesWithPhone);
            const alreadySent = parseInt(selectedOption.dataset.alreadySent);
            const willBeSent = attendeesWithPhone - alreadySent;
            
            // Update statistics
            statsDiv.innerHTML = `
                <div class="row g-2">
                    <div class="col-6">
                        <strong>Total Attendees:</strong> ${attendeesCount}
                    </div>
                    <div class="col-6">
                        <strong>With Phone:</strong> ${attendeesWithPhone}
                    </div>
                    <div class="col-6">
                        <strong>Already Sent:</strong> ${alreadySent}
                    </div>
                    <div class="col-6">
                        <strong>Will Receive:</strong> ${willBeSent}
                    </div>
                </div>
            `;
            
            // Enable/disable send button based on whether there are attendees to send to
            if (willBeSent > 0) {
                sendBtn.disabled = false;
                sendBtn.classList.remove('btn-secondary');
                sendBtn.classList.add('btn-success');
            } else {
                sendBtn.disabled = true;
                sendBtn.classList.remove('btn-success');
                sendBtn.classList.add('btn-secondary');
            }
        } else {
            statsDiv.innerHTML = 'Select an event to see statistics';
            sendBtn.disabled = true;
            sendBtn.classList.remove('btn-success');
            sendBtn.classList.add('btn-secondary');
        }
    });

    // Send Invitation SMS functionality
    document.getElementById('sendInvitationSmsBtn').addEventListener('click', async function() {
        const eventSelect = document.getElementById('eventSelect');
        const sendBtn = this;
        const originalText = sendBtn.innerHTML;
        
        if (!eventSelect.value) {
            toastr.error('Please select an event first');
            return;
        }
        
        // Confirm action
        if (!confirm('This will send invitation SMS to ALL attendees who haven\'t received invitations yet. Are you sure you want to continue?')) {
            return;
        }
        
        try {
            // Show loading state
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            
            const response = await fetch(`/sms/management/events/${eventSelect.value}/dispatch-invitation-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message with statistics
                let message = result.message;
                if (result.statistics) {
                    const stats = result.statistics;
                    message += `\n\nStatistics:\n• Total attendees: ${stats.total_attendees}\n• Attendees with phone: ${stats.attendees_with_phone}\n• Already sent invitations: ${stats.already_sent_count}\n• Will receive invitations: ${stats.will_be_sent_to}`;
                }
                
                toastr.success(message);
                
                // Update the event statistics
                const selectedOption = eventSelect.options[eventSelect.selectedIndex];
                selectedOption.dataset.alreadySent = parseInt(selectedOption.dataset.alreadySent) + (result.statistics?.will_be_sent_to || 0);
                
                // Trigger change event to update display
                eventSelect.dispatchEvent(new Event('change'));
                
            } else {
                toastr.error(result.message || 'Failed to dispatch invitation SMS job');
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred while processing the request');
        } finally {
            // Reset button state
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalText;
        }
    });

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