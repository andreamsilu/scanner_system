@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Verify Event Card</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="h1 mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h4>Valid Event Card</h4>
                        <p class="text-muted">This event card belongs to:</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 150px;">Event:</th>
                                <td>{{ $attendee->event->name }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $attendee->name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $attendee->email }}</td>
                            </tr>
                            @if($attendee->company)
                            <tr>
                                <th>Company:</th>
                                <td>{{ $attendee->company }}</td>
                            </tr>
                            @endif
                            @if($attendee->position)
                            <tr>
                                <th>Position:</th>
                                <td>{{ $attendee->position }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-{{ $attendee->status === 'registered' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($attendee->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-3">Registration Date: {{ $attendee->registration_date->format('F j, Y g:i A') }}</p>
                        @if(auth()->check())
                            <form action="{{ route('scan-logs.store') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="attendee_id" value="{{ $attendee->id }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Mark as Attended
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 