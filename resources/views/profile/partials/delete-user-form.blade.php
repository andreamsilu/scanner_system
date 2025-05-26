<section class="card">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="bi bi-trash me-2"></i>{{ __('Delete Account') }}
        </h5>
        <p class="text-muted small mb-0 mt-2">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </div>

    <div class="card-body">
        <button type="button" 
                class="btn btn-danger"
                data-bs-toggle="modal" 
                data-bs-target="#confirm-user-deletion">
            <i class="bi bi-trash me-2"></i>{{ __('Delete Account') }}
        </button>

        <!-- Delete Account Modal -->
        <div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                            {{ __('Delete Account') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('delete')

                        <div class="modal-body">
                            <p class="mb-0">
                                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>

                            <div class="mt-4">
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input type="password" 
                                       class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="{{ __('Enter your password') }}"
                                       required>
                                @error('password', 'userDeletion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i>{{ __('Delete Account') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
