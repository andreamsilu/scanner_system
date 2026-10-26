@props(['event', 'title' => 'Event registration'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
    <title>{{ $title }} · {{ $event->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="bg-zinc-50 text-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 antialiased">
    <main class="max-w-xl mx-auto px-6 py-12 sm:py-20">
        <p class="eyebrow mb-4">You're invited</p>
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight mb-4">{{ $event->name }}</h1>
        <div class="ui-muted text-sm space-y-2 mb-8">
            <p>{{ $event->start_date->format('D, d M Y · H:i') }} – {{ $event->end_date->format('d M Y · H:i') }}</p>
            <p>{{ $event->venue }}@if($event->location) · {{ $event->location }}@endif</p>
        </div>
        <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 sm:p-8">
            {{ $slot }}
        </section>
    </main>
    @livewireScripts
    @fluxScripts
</body>
</html>
