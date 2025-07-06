<x-app-layout>
    <x-page-header 
        title="Attendee Details"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Attendees' => route('events.attendees.index', $event),
            'Details' => null
        ]"
    >
        <x-slot name="actions">
            <div class="d-flex gap-2">

                <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Edit Attendee
                </a>
                <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Attendees
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Attendee Information -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-circle me-2"></i>Attendee Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Name</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Email</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->email }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Phone</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->phone ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Registration Status</label>
                                    <p class="mb-0">
                                        <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                            {{ ucfirst($attendee->registration_status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Registration Date</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->created_at->format('d-m-Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Last Updated</label>
                                    <p class="mb-0 fw-medium">{{ $attendee->updated_at->format('d-m-Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code Section -->
                        <div class="text-center mt-4 pt-3 border-top">
                            @if($attendee->qr_code)
                                @if(Storage::disk('public')->exists($attendee->qr_code))
                                    <div class="qr-code-container">
                                        <img src="{{ Storage::url($attendee->qr_code) }}" 
                                             alt="QR Code for {{ $attendee->name }}" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-width: 200px;">
                                        <p class="text-muted mt-2 mb-0 small">Registration QR Code</p>
                                        <div class="mt-2">
                                            <span class="badge bg-light text-dark border">Token: {{ $attendee->token }}</span>
                                        </div>
                                        <div class="mt-3 d-flex justify-content-center gap-2">
                                            <a href="{{ Storage::url($attendee->qr_code) }}" 
                                               download="{{ strtolower(str_replace(' ', '-', $attendee->name)) }}-qrcode-{{ $attendee->token }}.png"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-download me-2"></i>Download QR Code
                                            </a>
                                            <form action="{{ route('events.attendees.regenerate-qr', ['event' => $event, 'attendee' => $attendee]) }}" 
                                                  method="POST" 
                                                  class="d-inline-block"
                                                  onsubmit="return confirm('Are you sure you want to regenerate the QR code? This will invalidate the current one.')">
                                                @csrf
                                                <input type="hidden" name="_method" value="POST">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-repeat me-2"></i>Regenerate QR Code
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        QR code file not found. Please regenerate the QR code.
                                    </div>
                                    <form action="{{ route('events.attendees.regenerate-qr', ['event' => $event, 'attendee' => $attendee]) }}" 
                                          method="POST" 
                                          class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="_method" value="POST">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-qr-code me-2"></i>Generate QR Code
                                        </button>
                                    </form>
                                @endif
                            @else
                                <div class="text-muted">
                                    <i class="bi bi-qr-code fs-1 d-block mb-3"></i>
                                    <p class="mb-3">No QR code generated for this attendee.</p>
                                    <form action="{{ route('events.attendees.regenerate-qr', ['event' => $event, 'attendee' => $attendee]) }}" 
                                          method="POST" 
                                          class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="_method" value="POST">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-qr-code me-2"></i>Generate QR Code
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Ticket Information -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-ticket-perforated me-2"></i>Ticket Information
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($attendee->ticket)
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Ticket Number</label>
                                        <p class="mb-0 fw-medium">{{ $attendee->ticket->ticket_number }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Status</label>
                                        <p class="mb-0">
                                            <span class="badge bg-{{ $attendee->ticket->status === 'valid' ? 'success' : 'danger' }}">
                                                {{ ucfirst($attendee->ticket->status) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Created At</label>
                                        <p class="mb-0 fw-medium">{{ $attendee->ticket->created_at->format('d-m-Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Last Updated</label>
                                        <p class="mb-0 fw-medium">{{ $attendee->ticket->updated_at->format('d-m-Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <img src="{{ $attendee->ticket->qr_code_url }}" 
                                     alt="Ticket QR Code" 
                                     class="img-fluid rounded shadow-sm" 
                                     style="max-width: 200px;">
                                <p class="text-muted mt-2 mb-0 small">Ticket QR Code</p>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-ticket-perforated-x fs-1 d-block mb-3"></i>
                                    <p class="mb-3">No ticket assigned to this attendee.</p>
                                </div>
                                <a href="{{ route('events.tickets.create', [$event, $attendee]) }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Generate Ticket
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Scan History -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Scan History
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($attendee->ticket && $attendee->ticket->scan_logs && $attendee->ticket->scan_logs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Scanner</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attendee->ticket->scan_logs as $log)
                                            <tr>
                                                <td>{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                                                <td>{{ $log->scanner->name ?? 'System' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $log->status === 'valid' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($log->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $log->notes ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-clock-history fs-1 d-block mb-3"></i>
                                <p class="mb-0">No scan history available for this attendee.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Event Information and Quick Actions -->
            <div class="col-md-4">
                <!-- Event Information -->
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-event me-2"></i>Event Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Event Name</label>
                            <p class="mb-0 fw-medium">{{ $event->name }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Date</label>
                            <p class="mb-0 fw-medium">
                                {{ $event->start_date->format('d-m-Y') }} - 
                                {{ $event->end_date->format('d-m-Y') }}
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Venue</label>
                            <p class="mb-0 fw-medium">{{ $event->venue }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Location</label>
                            <p class="mb-0 fw-medium">{{ $event->location }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted small">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $event->status === 'active' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($attendee->ticket)
                                <button type="button"
                                        class="btn btn-outline-primary"
                                        onclick="downloadTicket('{{ route('events.events.tickets.print', [$event, $attendee->ticket]) }}')">
                                    <i class="bi bi-download me-2"></i>Download Ticket
                                </button>
                                @if($attendee->ticket->status === 'valid')
                                    <form action="{{ route('events.events.tickets.deactivate', [$event, $attendee->ticket]) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to invalidate this ticket? This action cannot be undone.')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-warning w-100">
                                            <i class="bi bi-x-circle me-2"></i>Invalidate Ticket
                                        </button>
                                    </form>
                                @endif
                            @endif
                            <button type="button" 
                                    class="btn btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteAttendeeModal">
                                <i class="bi bi-trash me-2"></i>Delete Attendee
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteAttendeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Delete Attendee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this attendee? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('events.attendees.destroy', [$event, $attendee]) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Delete Attendee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadTicket(url) {
            // Create a temporary link element
            const link = document.createElement('a');
            link.href = url;
            link.download = ''; // This will use the filename from the server
            link.style.display = 'none';
            
            // Append to body, click, and remove
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</x-app-layout> 