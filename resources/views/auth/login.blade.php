<x-guest-layout>
    <p class="eyebrow mb-3">Your event workspace</p>
    <h1 class="text-3xl font-semibold tracking-tight mb-3">Welcome back</h1>
    <p class="ui-muted text-sm mb-8">Sign in to manage your events or welcome guests.</p>
    @if(session('status'))<p class="scan-result mb-5 text-sm" data-status="success" role="status">{{ session('status') }}</p>@endif
    <form action="{{ route('login') }}" method="POST" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf
        <flux:input label="Email address" type="email" name="email" id="email" :value="old('email')" placeholder="you@example.com" autocomplete="username" required autofocus />
        <flux:input label="Password" type="password" name="password" id="password" autocomplete="current-password" required viewable />
        <flux:checkbox name="remember" label="Keep me signed in" :checked="old('remember', false)" />
        <flux:button type="submit" variant="primary" class="w-full min-h-12!" x-bind:disabled="submitting"><span x-text="submitting ? 'Signing in…' : 'Sign in'">Sign in</span></flux:button>
    </form>
</x-guest-layout>
