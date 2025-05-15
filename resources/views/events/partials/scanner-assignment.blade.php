<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Assigned Scanners</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignScannerModal">
                <i class="fas fa-plus me-2"></i>Assign Scanner
            </button>
        </div>
    </div>
    <div class="card-body">
        @if($event->scanners->isEmpty())
            <p class="text-muted mb-0">No scanners assigned to this event yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($event->scanners as $scanner)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                            <span class="text-primary fw-bold">
                                                {{ substr($scanner->name, 0, 1) }}
                                            </span>
                                        </div>
                                        {{ $scanner->name }}
                                    </div>
                                </td>
                                <td>{{ $scanner->email }}</td>
                                <td>
                                    @if($scanner->isActive())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $scanner->pivot->assigned_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editScannerModal{{ $scanner->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('events.scanners.remove', [$event, $scanner]) }}" 
                                            method="POST" 
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to remove this scanner?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Assign Scanner Modal -->
<div class="modal fade" id="assignScannerModal" tabindex="-1" aria-labelledby="assignScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('events.scanners.assign', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignScannerModalLabel">
                        <i class="bi bi-person-plus me-2"></i>Assign Scanner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id'))->isEmpty())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No available scanners to assign. All scanner users are already assigned to this event.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="scanner_id" class="form-label">Select Scanner</label>
                            <select class="form-select" id="scanner_id" name="scanner_id" required>
                                <option value="">Choose a scanner...</option>
                                @foreach($scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id')) as $scanner)
                                    <option value="{{ $scanner->id }}" 
                                            data-email="{{ $scanner->email }}"
                                            data-status="{{ $scanner->is_active ? 'Active' : 'Inactive' }}"
                                            data-last-active="{{ $scanner->last_active_at?->diffForHumans() ?? 'Never' }}">
                                        {{ $scanner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="scannerDetails" class="card d-none">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Email:</strong>
                                            <span id="scannerEmail"></span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Status:</strong>
                                            <span id="scannerStatus"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Last Active:</strong>
                                            <span id="scannerLastActive"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id'))->isEmpty())
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Assign Scanner
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scannerSelect = document.getElementById('scanner_id');
    const scannerDetails = document.getElementById('scannerDetails');
    const scannerEmail = document.getElementById('scannerEmail');
    const scannerStatus = document.getElementById('scannerStatus');
    const scannerLastActive = document.getElementById('scannerLastActive');

    if (scannerSelect) {
        scannerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value) {
                scannerEmail.textContent = selectedOption.dataset.email;
                scannerStatus.textContent = selectedOption.dataset.status;
                scannerLastActive.textContent = selectedOption.dataset.lastActive;
                
                // Add status badge
                const statusClass = selectedOption.dataset.status === 'Active' ? 'success' : 'secondary';
                scannerStatus.innerHTML = `<span class="badge bg-${statusClass}">${selectedOption.dataset.status}</span>`;
                
                scannerDetails.classList.remove('d-none');
            } else {
                scannerDetails.classList.add('d-none');
            }
        });
    }
});
</script>
@endpush

<!-- Edit Scanner Modals -->
@foreach($event->scanners as $scanner)
<div class="modal fade" id="editScannerModal{{ $scanner->id }}" tabindex="-1" aria-labelledby="editScannerModalLabel{{ $scanner->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('scanners.update', [$event, $scanner]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editScannerModalLabel{{ $scanner->id }}">
                        <i class="bi bi-person-gear me-2"></i>Change Scanner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Current Scanner: <strong>{{ $scanner->name }}</strong>
                    </div>
                    
                    @php
                        $availableScanners = $scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id'));
                    @endphp
                    
                    @if($availableScanners->isEmpty())
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No available scanners to replace the current one.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="new_scanner_id{{ $scanner->id }}" class="form-label">Select New Scanner</label>
                            <select class="form-select" id="new_scanner_id{{ $scanner->id }}" name="new_scanner_id" required>
                                <option value="">Choose a scanner...</option>
                                @foreach($availableScanners as $newScanner)
                                    <option value="{{ $newScanner->id }}"
                                            data-email="{{ $newScanner->email }}"
                                            data-status="{{ $newScanner->is_active ? 'Active' : 'Inactive' }}"
                                            data-last-active="{{ $newScanner->last_active_at?->diffForHumans() ?? 'Never' }}">
                                        {{ $newScanner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="newScannerDetails{{ $scanner->id }}" class="card d-none">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Email:</strong>
                                            <span id="newScannerEmail{{ $scanner->id }}"></span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Status:</strong>
                                            <span id="newScannerStatus{{ $scanner->id }}"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Last Active:</strong>
                                            <span id="newScannerLastActive{{ $scanner->id }}"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$availableScanners->isEmpty())
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-gear me-2"></i>Update Scanner
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const newScannerSelect = document.getElementById('new_scanner_id{{ $scanner->id }}');
    const newScannerDetails = document.getElementById('newScannerDetails{{ $scanner->id }}');
    const newScannerEmail = document.getElementById('newScannerEmail{{ $scanner->id }}');
    const newScannerStatus = document.getElementById('newScannerStatus{{ $scanner->id }}');
    const newScannerLastActive = document.getElementById('newScannerLastActive{{ $scanner->id }}');

    if (newScannerSelect) {
        newScannerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value) {
                newScannerEmail.textContent = selectedOption.dataset.email;
                newScannerStatus.textContent = selectedOption.dataset.status;
                newScannerLastActive.textContent = selectedOption.dataset.lastActive;
                
                // Add status badge
                const statusClass = selectedOption.dataset.status === 'Active' ? 'success' : 'secondary';
                newScannerStatus.innerHTML = `<span class="badge bg-${statusClass}">${selectedOption.dataset.status}</span>`;
                
                newScannerDetails.classList.remove('d-none');
            } else {
                newScannerDetails.classList.add('d-none');
            }
        });
    }
});
</script>
@endpush
@endforeach 