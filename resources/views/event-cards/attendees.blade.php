@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-users me-2"></i>Event Attendees
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Event Details -->
                        <a href="{{ route('events.show', $event) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Event Details
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>All users
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Attendees Page -->
                        <a href="{{ route('event-cards.attendees', ['event' => $event->id]) }}" class="list-group-item list-group-item-action active">
                            <div>
                                <i class="fas fa-users me-2"></i>
                                Attendees
                                <span class="badge bg-primary rounded-pill ms-2">{{ $event->attendees_count ?? 0 }}</span>
                                <small class="d-block text-white mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                        </a>

                        <!-- All Cards -->
                        <a href="{{ route('event-cards.index', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-id-card me-2 text-primary"></i>
                                All Cards
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Create New Card -->
                        <a href="{{ route('event-cards.create', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-plus-circle me-2 text-primary"></i>
                                Create New Card
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Import Attendees -->
                        <a href="{{ route('event-cards.import', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-import me-2 text-primary"></i>
                                Import Attendees
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers only
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Export Attendees -->
                        <a href="{{ route('event-cards.export', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-export me-2 text-primary"></i>
                                Export Attendees
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Manage Access -->
                        <a href="{{ route('event-cards.access', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-lock me-2 text-primary"></i>
                                Manage Access
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Event organizers only
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>

                        <!-- Check-in Station -->
                        <a href="{{ route('event-cards.checkin', ['event' => $event->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-qrcode me-2 text-primary"></i>
                                Check-in Station
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-user-shield me-1"></i>Check-in staff & admins
                                </small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Quick Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total Attendees</span>
                        <span class="badge bg-primary">{{ $event->attendees_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Remaining Capacity</span>
                        <span class="badge bg-success">{{ $event->remaining_capacity }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Event Status</span>
                        <span class="badge bg-{{ $event->status_badge }}">{{ ucfirst($event->status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attendees List</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-file-import me-2"></i>Import
                            </button>
                            <a href="{{ route('event-cards.export', ['event' => $event->id]) }}" class="btn btn-success">
                                <i class="fas fa-file-export me-2"></i>Export
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search and Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search attendees...">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex gap-2">
                                <select class="form-select" id="ticketTypeFilter">
                                    <option value="">All Ticket Types</option>
                                    <option value="regular">Regular</option>
                                    <option value="vip">VIP</option>
                                    <option value="speaker">Speaker</option>
                                    <option value="sponsor">Sponsor</option>
                                </select>
                                <select class="form-select" id="companyFilter">
                                    <option value="">All Companies</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company }}">{{ $company }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="checked_in">Checked In</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Attendees Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Ticket Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendees as $attendee)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initials bg-primary text-white">
                                                        {{ substr($attendee->name, 0, 2) }}
                                                    </span>
                                                </div>
                                                {{ $attendee->name }}
                                            </div>
                                        </td>
                                        <td>{{ $attendee->email }}</td>
                                        <td>{{ $attendee->company }}</td>
                                        <td>
                                            <span class="badge bg-{{ $attendee->ticket_type_badge }}">
                                                {{ ucfirst($attendee->ticket_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $attendee->status_badge }}">
                                                {{ ucfirst($attendee->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-2x mb-3"></i>
                                                <p class="mb-0">No attendees found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing {{ $attendees->firstItem() ?? 0 }} to {{ $attendees->lastItem() ?? 0 }} of {{ $attendees->total() }} entries
                        </div>
                        <div>
                            {{ $attendees->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-initials {
        font-size: 0.875rem;
        font-weight: 600;
    }
    .table > :not(caption) > * > * {
        padding: 1rem;
    }
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
    .form-select {
        padding-right: 2rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('table');
    const rows = table.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                    found = true;
                    break;
                }
            }

            row.style.display = found ? '' : 'none';
        }
    });

    // Filter functionality
    const filters = ['ticketTypeFilter', 'companyFilter', 'statusFilter'];
    
    filters.forEach(filterId => {
        const filter = document.getElementById(filterId);
        filter.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const ticketType = document.getElementById('ticketTypeFilter').value.toLowerCase();
        const company = document.getElementById('companyFilter').value.toLowerCase();
        const status = document.getElementById('statusFilter').value.toLowerCase();

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            
            const rowTicketType = cells[3].textContent.toLowerCase();
            const rowCompany = cells[2].textContent.toLowerCase();
            const rowStatus = cells[4].textContent.toLowerCase();

            const matchesTicketType = !ticketType || rowTicketType.includes(ticketType);
            const matchesCompany = !company || rowCompany.includes(company);
            const matchesStatus = !status || rowStatus.includes(status);

            row.style.display = (matchesTicketType && matchesCompany && matchesStatus) ? '' : 'none';
        }
    }
});
</script>
@endpush
@endsection 