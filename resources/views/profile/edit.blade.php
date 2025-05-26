<x-app-layout>
    <x-page-header 
        title="Profile Settings"
        :breadcrumbs="[
            'Dashboard' => route('dashboard'),
            'Profile' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
