@extends('layouts.app')

@section('title', $event->name . ' - Attendees')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ $event->name }} - Attendees</h2>
                        <div>
            <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary me-2">
                <i class="bi bi-person-plus me-2"></i>Add Attendee
                            </a>
            <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Event
                            </a>
                        </div>
                    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
    @endif

    <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="attendeesTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Ticket Status</th>
                                    <th>Registered At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendees as $attendee)
                                    <tr>
                                        <td>{{ $attendee->name }}</td>
                                        <td>{{ $attendee->email }}</td>
                                        <td>{{ $attendee->phone ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'info') }}">
                                                {{ ucfirst($attendee->registration_status ?? 'registered') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($attendee->ticket)
                                                <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'active' ? 'primary' : 'warning') }}">
                                                    {{ ucfirst($attendee->ticket->status) }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">No Ticket</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span title="{{ $attendee->created_at ? $attendee->created_at->format('F j, Y') : 'N/A' }}">
                                                {{ $attendee->created_at ? $attendee->created_at->format('M d, Y') : 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                        <a href="{{ route('events.attendees.show', [$event, $attendee]) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('events.attendees.edit', [$event, $attendee]) }}" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Edit Attendee">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteAttendeeModal{{ $attendee->id }}"
                                                title="Delete Attendee">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteAttendeeModal{{ $attendee->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete Attendee</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete this attendee? This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="{{ route('events.attendees.destroy', [$event, $attendee]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Delete Attendee</button>
                                                </form>
                                                </div>
                                            </div>
                                        </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">No attendees registered yet.</p>
                                </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

            <div class="d-flex justify-content-end mt-4">
                        {{ $attendees->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#attendeesTable').DataTable({
            responsive: true,
            processing: true,
            pageLength: 10,
            order: [[5, 'desc']], // Sort by registration date by default
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on actions column
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search attendees...",
                emptyTable: "No attendees registered yet."
            }
        });
    });
</script>
@endpush
@endsection 