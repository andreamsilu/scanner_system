<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Assigned Scanners</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignScannerModal">
                <i class="bi bi-person-plus me-2"></i>Assign Scanner
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
                                <td>{{ $scanner->name }}</td>
                                <td>{{ $scanner->email }}</td>
                                <td>
                                    @if($scanner->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($scanner->pivot->assigned_at)
                                        {{ is_string($scanner->pivot->assigned_at) 
                                            ? \Carbon\Carbon::parse($scanner->pivot->assigned_at)->format('M d, Y h:i A')
                                            : $scanner->pivot->assigned_at->format('M d, Y h:i A') 
                                        }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editScannerModal{{ $scanner->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('events.scanners.remove', [$event, $scanner]) }}" 
                                            method="POST" 
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to remove this scanner?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
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
                    @php
                        $availableScanners = $scannerUsers->whereNotIn('id', $event->scanners->pluck('id'));
                    @endphp

                    @if($availableScanners->isEmpty())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No available scanners to assign. All scanner users are already assigned to this event.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="scanner_id" class="form-label">Select Scanner</label>
                            <select class="form-select" id="scanner_id" name="scanner_id" required>
                                <option value="">Choose a scanner...</option>
                                @foreach($availableScanners as $scanner)
                                    <option value="{{ $scanner->id }}" 
                                            data-email="{{ $scanner->email }}"
                                            data-status="{{ $scanner->is_active ? 'Active' : 'Inactive' }}"
                                            data-last-active="{{ $scanner->last_login_at?->diffForHumans() ?? 'Never' }}">
                                        {{ $scanner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="scannerDetails" class="d-none">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Scanner Details</h6>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3">Email:</dt>
                                        <dd class="col-sm-9" id="scannerEmail"></dd>
                                        
                                        <dt class="col-sm-3">Status:</dt>
                                        <dd class="col-sm-9" id="scannerStatus"></dd>
                                        
                                        <dt class="col-sm-3">Last Active:</dt>
                                        <dd class="col-sm-9" id="scannerLastActive"></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$availableScanners->isEmpty())
                        <button type="submit" class="btn btn-primary">Assign Scanner</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Scanner Modals -->
@foreach($event->scanners as $scanner)
<div class="modal fade" id="editScannerModal{{ $scanner->id }}" tabindex="-1" aria-labelledby="editScannerModalLabel{{ $scanner->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('events.scanners.update', [$event, $scanner]) }}" method="POST">
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
                        $availableScanners = $scannerUsers->whereNotIn('id', $event->scanners->pluck('id'));
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
                                            data-last-active="{{ $newScanner->last_login_at?->diffForHumans() ?? 'Never' }}">
                                        {{ $newScanner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="newScannerDetails{{ $scanner->id }}" class="d-none">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">New Scanner Details</h6>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3">Email:</dt>
                                        <dd class="col-sm-9" id="newScannerEmail{{ $scanner->id }}"></dd>
                                        
                                        <dt class="col-sm-3">Status:</dt>
                                        <dd class="col-sm-9" id="newScannerStatus{{ $scanner->id }}"></dd>
                                        
                                        <dt class="col-sm-3">Last Active:</dt>
                                        <dd class="col-sm-9" id="newScannerLastActive{{ $scanner->id }}"></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if(!$availableScanners->isEmpty())
                        <button type="submit" class="btn btn-primary">Update Scanner</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle scanner selection in assign modal
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
                scannerStatus.innerHTML = `<span class="badge bg-${selectedOption.dataset.status === 'Active' ? 'success' : 'danger'}">${selectedOption.dataset.status}</span>`;
                scannerLastActive.textContent = selectedOption.dataset.lastActive;
                scannerDetails.classList.remove('d-none');
            } else {
                scannerDetails.classList.add('d-none');
            }
        });
    }

    // Handle scanner selection in edit modals
    @foreach($event->scanners as $scanner)
    const newScannerSelect{{ $scanner->id }} = document.getElementById('new_scanner_id{{ $scanner->id }}');
    const newScannerDetails{{ $scanner->id }} = document.getElementById('newScannerDetails{{ $scanner->id }}');
    const newScannerEmail{{ $scanner->id }} = document.getElementById('newScannerEmail{{ $scanner->id }}');
    const newScannerStatus{{ $scanner->id }} = document.getElementById('newScannerStatus{{ $scanner->id }}');
    const newScannerLastActive{{ $scanner->id }} = document.getElementById('newScannerLastActive{{ $scanner->id }}');

    if (newScannerSelect{{ $scanner->id }}) {
        newScannerSelect{{ $scanner->id }}.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value) {
                newScannerEmail{{ $scanner->id }}.textContent = selectedOption.dataset.email;
                newScannerStatus{{ $scanner->id }}.innerHTML = `<span class="badge bg-${selectedOption.dataset.status === 'Active' ? 'success' : 'danger'}">${selectedOption.dataset.status}</span>`;
                newScannerLastActive{{ $scanner->id }}.textContent = selectedOption.dataset.lastActive;
                newScannerDetails{{ $scanner->id }}.classList.remove('d-none');
            } else {
                newScannerDetails{{ $scanner->id }}.classList.add('d-none');
            }
        });
    }
    @endforeach
});
</script>
@endpush 