<section class="card mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">
            <i class="bi bi-person-circle me-2"></i>{{ __('Profile Information') }}
        </h5>
        <p class="text-muted small mb-0 mt-2">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </div>

    <div class="card-body">
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="mt-4">
            @csrf
            @method('patch')

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               required 
                               autofocus 
                               autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}" 
                               required 
                               autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <p class="mb-0">
                                {{ __('Your email address is unverified.') }}
                                <button form="send-verification" class="btn btn-link p-0 m-0 align-baseline">
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>
                            </p>
                        </div>

                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="col-12">
                    <div class="d-flex align-items-center gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>{{ __('Save') }}
                        </button>

                        @if (session('status') === 'profile-updated')
                            <div class="alert alert-success mb-0 py-2">
                                <i class="bi bi-check-circle me-2"></i>{{ __('Saved.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
