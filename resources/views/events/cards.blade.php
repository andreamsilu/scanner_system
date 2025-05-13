@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Event Cards - {{ $event->name }}</h5>
                        <div>
                            <a href="{{ route('event-cards.create', ['event_id' => $event->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Card
                            </a>
                            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Event
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="cardsTable">
                            <thead>
                                <tr>
                                    <th>Card ID</th>
                                    <th>Attendee</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cards as $card)
                                    <tr>
                                        <td>{{ $card->token }}</td>
                                        <td>{{ $card->attendee->name }}</td>
                                        <td>{{ ucfirst($card->type) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $card->is_active ? 'success' : 'danger' }}">
                                                {{ $card->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $card->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('event-cards.show', $card) }}" 
                                                   class="btn btn-sm btn-info" 
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('event-cards.edit', $card) }}" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('event-cards.download', $card) }}" 
                                                   class="btn btn-sm btn-success" 
                                                   title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <form action="{{ route('event-cards.destroy', $card) }}" 
                                                      method="POST" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this card?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No cards found for this event.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $cards->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#cardsTable').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            order: [[4, 'desc']]
        });
    });
</script>
@endpush
@endsection 