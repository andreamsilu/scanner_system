@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Profile Information') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email Address') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>

                            @if (session('status') === 'profile-updated')
                                <div class="alert alert-success mb-0" role="alert">
                                    {{ __('Profile updated successfully.') }}
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">{{ __('Delete Account') }}</div>

                <div class="card-body">
                    <p class="text-muted">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
                    </p>

                    <form method="POST" action="{{ route('profile.destroy') }}" class="mt-3">
                        @csrf
                        @method('delete')

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input id="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" name="password" required>
                            @error('password', 'userDeletion')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('Are you sure you want to delete your account?') }}')">
                            {{ __('Delete Account') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 