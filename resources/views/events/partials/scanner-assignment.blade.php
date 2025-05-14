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
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('events.scanners.assign', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignScannerModalLabel">Assign Scanner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="scanner_id" class="form-label">Select Scanner</label>
                        <select class="form-select" id="scanner_id" name="scanner_id" required>
                            <option value="">Choose a scanner...</option>
                            @foreach($scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id')) as $scanner)
                                <option value="{{ $scanner->id }}">{{ $scanner->name }} ({{ $scanner->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Scanner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Scanner Modals -->
@foreach($event->scanners as $scanner)
<div class="modal fade" id="editScannerModal{{ $scanner->id }}" tabindex="-1" aria-labelledby="editScannerModalLabel{{ $scanner->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('events.scanners.update', [$event, $scanner]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editScannerModalLabel{{ $scanner->id }}">Change Scanner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_scanner_id{{ $scanner->id }}" class="form-label">Select New Scanner</label>
                        <select class="form-select" id="new_scanner_id{{ $scanner->id }}" name="new_scanner_id" required>
                            <option value="">Choose a scanner...</option>
                            @foreach($scannerUsers->whereNotIn('id', $event->scanners->pluck('user_id')) as $newScanner)
                                <option value="{{ $newScanner->id }}">{{ $newScanner->name }} ({{ $newScanner->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Scanner</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach 