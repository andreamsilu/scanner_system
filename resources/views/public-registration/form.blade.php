<x-public-event-layout :event="$event">
    @if($closedReason)
        <h2 class="text-xl font-semibold mb-3">Registration closed</h2>
        <p class="ui-muted" role="status">{{ $closedReason }}</p>
    @else
        <h2 class="text-xl font-semibold mb-2">Reserve your place</h2>
        <p class="ui-muted text-sm mb-6">Register below to receive your single-entry ticket. No account needed.</p>
        @error('registration')<p class="text-red-600 mb-4" role="alert">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('public-registration.store', $event->public_registration_token) }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf
            <flux:input label="Full name" name="name" :value="old('name')" autocomplete="name" maxlength="255" required />
            <flux:input label="Phone number" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="+255 712 345 678" maxlength="25" required />
            <flux:input label="Email address (optional)" type="email" name="email" :value="old('email')" autocomplete="email" maxlength="255" />
            @if(config('services.whatsapp.enabled'))<flux:checkbox name="whatsapp_consent" value="1" :checked="old('whatsapp_consent', false)" label="I agree to receive invitations and ticket details for this event on WhatsApp. I can reply STOP to opt out." />@endif
            <p class="ui-muted text-xs">Your details will be shared with the event organizer to manage your registration.</p>
            <flux:button type="submit" variant="primary" class="w-full" x-bind:disabled="submitting"><span x-text="submitting ? 'Registering…' : 'Register for event'">Register for event</span></flux:button>
        </form>
    @endif
</x-public-event-layout>
