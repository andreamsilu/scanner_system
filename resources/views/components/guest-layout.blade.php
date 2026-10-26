<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('app.name', 'Event Tickets') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100">
    <div class="min-h-dvh grid lg:grid-cols-2">
        <aside class="hidden lg:flex flex-col justify-between p-12 xl:p-16 bg-teal-950 text-white relative overflow-hidden">
            <a href="{{ url('/') }}" class="workspace-brand relative z-10 text-white"><span class="brand-symbol bg-teal-800!"><flux:icon.ticket class="size-6" /></span>Event Tickets</a>
            <div class="relative z-10 max-w-md my-16">
                <div class="grid size-20 place-items-center rounded-3xl bg-teal-800/60 border border-teal-700 mb-10"><flux:icon.qr-code class="size-12 text-teal-200" /></div>
                <p class="text-teal-300 text-xs font-semibold tracking-widest uppercase mb-4">From invitation to admission</p>
                <h1 class="text-5xl xl:text-6xl font-semibold tracking-tight leading-tight mb-6">Great events.<br>A warm welcome.</h1>
                <p class="text-teal-100/80 leading-relaxed">Bring your team, tickets and check-ins together in one workspace. Make every guest's arrival feel effortless.</p>
            </div>
            <p class="text-sm text-teal-200/70 relative z-10">Every entry, accounted for.</p>
            <div aria-hidden="true" class="absolute -bottom-44 -right-44 size-[650px] rounded-full border-[80px] border-teal-900/60"></div>
        </aside>
        <main class="flex flex-col justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm mx-auto">
                <a href="{{ url('/') }}" class="workspace-brand lg:hidden mb-12"><span class="brand-symbol"><flux:icon.ticket class="size-6" /></span>Event Tickets</a>
                {{ $slot }}
                <p class="text-xs text-zinc-500 mt-10">Need access? Contact your event administrator.</p>
            </div>
        </main>
    </div>
    @livewireScripts
    @fluxScripts
</body>
</html>
