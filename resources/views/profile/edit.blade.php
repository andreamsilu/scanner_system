<x-app-layout>
    <x-slot name="header">
        <h2 class="mb-0">Profile Settings</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
