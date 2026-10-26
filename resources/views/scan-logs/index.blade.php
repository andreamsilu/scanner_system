<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Scan Logs</h2>
            <a href="{{ route('scan-logs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>New Scan
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Scanned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scanLogs as $log)
                            <tr>
                                <td>{{ $log->event->name }}</td>
                                <td>{{ $log->user->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->status === 'valid' ? 'success' : 'danger' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('scan-logs.show', $log) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($log->status === 'valid')
                                            <form action="{{ route('scan-logs.invalidate', $log) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Are you sure you want to invalidate this scan?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No scan logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
    @endpush
</x-app-layout> 