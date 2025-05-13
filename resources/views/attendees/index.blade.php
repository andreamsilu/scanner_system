@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Attendees</h2>
        <div>
            <a href="{{ route('attendees.bulk-upload') }}" class="btn btn-success me-2">
                <i class="fas fa-upload me-2"></i>Bulk Upload
            </a>
            <a href="{{ route('attendees.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Attendee
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="attendeesTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Event</th>
                    <th>Ticket Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendees as $attendee)
                    <tr>
                        <td>{{ $attendee->name }}</td>
                        <td>{{ $attendee->email }}</td>
                        <td>{{ $attendee->event->name }}</td>
                        <td>{{ $attendee->ticketType->name }}</td>
                        <td>
                            @if($attendee->status === 'registered')
                                <span class="badge bg-primary">Registered</span>
                            @elseif($attendee->status === 'checked_in')
                                <span class="badge bg-success">Checked In</span>
                            @else
                                <span class="badge bg-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('attendees.show', $attendee) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('attendees.edit', $attendee) }}" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('attendees.destroy', $attendee) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this attendee?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No attendees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
<script src="{{ asset('js/datatables-config.js') }}"></script>

<script>
    $(document).ready(function() {
        initDataTable('#attendeesTable', {
            order: [[0, 'asc']], // Sort by name by default
            columnDefs: [
                { orderable: false, targets: [5] } // Disable sorting on actions column
            ]
        });
    });
</script>
@endpush
@endsection 