@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Event Cards</h3>
                    <div class="card-tools">
                        <a href="{{ route('event-cards.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Card
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Attendee</th>
                                    <th>Token</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cards as $card)
                                    <tr>
                                        <td>{{ $card->event->name }}</td>
                                        <td>{{ $card->attendee->name }}</td>
                                        <td>{{ $card->token }}</td>
                                        <td>
                                            <span class="badge bg-{{ $card->is_active ? 'success' : 'danger' }}">
                                                {{ $card->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $card->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('event-cards.show', $card) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('event-cards.edit', $card) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('event-cards.download', $card) }}" class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <form action="{{ route('event-cards.destroy', $card) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this card?')">
                                                        <i class="fas fa-trash"></i>
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

                    <div class="mt-4">
                        {{ $cards->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 