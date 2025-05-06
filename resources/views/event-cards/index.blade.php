@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Event Cards</h2>
        <a href="{{ route('event-cards.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create New Card
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('event-cards') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by name, email, or QR token..."
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="scanned" {{ request('status') === 'scanned' ? 'selected' : '' }}>
                            Scanned
                        </option>
                        <option value="not_scanned" {{ request('status') === 'not_scanned' ? 'selected' : '' }}>
                            Not Scanned
                        </option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Event Cards Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendees as $attendee)
                            <tr>
                                <td>{{ $attendee->name }}</td>
                                <td>{{ $attendee->email }}</td>
                                <td>{{ $attendee->company }}</td>
                                <td>{{ $attendee->position }}</td>
                                <td>
                                    @if($attendee->scanned)
                                        <span class="badge bg-success">Scanned</span>
                                    @else
                                        <span class="badge bg-secondary">Not Scanned</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('event-cards.edit', $attendee) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('event-card.download', $attendee) }}" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="{{ route('event-cards.destroy', $attendee) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No event cards found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $attendees->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Add any JavaScript functionality here
</script>
@endpush
@endsection 