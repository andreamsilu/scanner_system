<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Edit Scanner</h2>
            <div>
                <a href="{{ route('scanners.show', $scanner) }}" class="btn btn-outline-primary me-2">
                    <i class="bi bi-eye me-2"></i>View Scanner
                </a>
                <a href="{{ route('scanners.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Scanners
                </a>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('scanners.update', $scanner) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group mb-3">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $scanner->name) }}" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email', $scanner->email) }}" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password">
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" class="form-control" 
                           id="password_confirmation" name="password_confirmation">
                </div>

                <div class="form-group mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="is_active" 
                               name="is_active" value="1" {{ $scanner->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active Account</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Update Scanner
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 