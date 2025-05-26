<section class="card mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="bi bi-key me-2"></i>{{ __('Update Password') }}
        </h5>
        <p class="text-muted small mb-0 mt-2">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </div>

    <div class="card-body">
        <form method="post" action="{{ route('password.update') }}" class="mt-4">
            @csrf
            @method('put')

            <div class="row g-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="update_password_current_password" class="form-label">
                            {{ __('Current Password') }}
                        </label>
                        <input type="password" 
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                               id="update_password_current_password" 
                               name="current_password" 
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="update_password_password" class="form-label">
                            {{ __('New Password') }}
                        </label>
                        <input type="password" 
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                               id="update_password_password" 
                               name="password" 
                               autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="update_password_password_confirmation" class="form-label">
                            {{ __('Confirm Password') }}
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="update_password_password_confirmation" 
                               name="password_confirmation" 
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex align-items-center gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-2"></i>{{ __('Update Password') }}
                        </button>

                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success mb-0 py-2">
                                <i class="bi bi-check-circle me-2"></i>{{ __('Password updated successfully.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
