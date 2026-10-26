@php
    $isAdmin = auth()->user()->isAdmin();
    $usesLegacyUi = ! request()->routeIs('admin.dashboard', 'scanner.dashboard', 'scanner.scan.*', 'scanner.events.index', 'events.tickets.index', 'events.guests.index');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Event Tickets') }}</title>
    @if($usesLegacyUi)
        <link rel="stylesheet" href="{{ \App\Support\FrontendAssets::url('bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ \App\Support\FrontendAssets::url('dataTables.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ \App\Support\FrontendAssets::url('toastr.min.css') }}">
        @vite('resources/css/legacy.css')
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
    @stack('meta')
    @stack('styles')
</head>
<body class="workspace antialiased">
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('layouts.navigation')
    <flux:header class="workspace-topbar">
        <div class="flex items-center gap-3 min-w-0">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />
            <div>
                <p class="eyebrow mb-1">{{ $isAdmin ? 'Event workspace' : 'Admission workspace' }}</p>
                <p class="text-sm font-medium mb-0">{{ now()->format('l, d F Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="subtle" square x-data x-on:click="$flux.dark = ! $flux.dark" aria-label="Toggle dark mode">
                <flux:icon.sun class="size-5 hidden dark:block" />
                <flux:icon.moon class="size-5 dark:hidden" />
            </flux:button>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-inherit no-underline" aria-label="Your profile">
                <span class="grid size-9 place-items-center rounded-full bg-teal-100 text-teal-800 text-sm font-semibold dark:bg-teal-900 dark:text-teal-200">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="hidden sm:block text-sm font-medium max-w-40 truncate">{{ auth()->user()->name }}</span>
            </a>
        </div>
    </flux:header>
    <flux:main class="p-0! min-w-0">
        <main id="main-content" class="workspace-content legacy-content {{ $usesLegacyUi ? 'legacy-ui' : '' }}" tabindex="-1">
            @if(isset($pageHeader))
                <header class="mb-6">{{ $pageHeader }}</header>
            @endif
            @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $tone)
                @if(session($key))
                    <div class="scan-result mb-5" data-status="{{ $tone }}" role="status">{{ session($key) }}</div>
                @endif
            @endforeach
            {{ $content ?? '' }}
            @yield('content')
        </main>
    </flux:main>
    @if($usesLegacyUi)
        {{-- Compatibility assets are only needed by existing administration screens. --}}
        <script src="{{ \App\Support\FrontendAssets::url('jquery.min.js') }}"></script>
        <script src="{{ \App\Support\FrontendAssets::url('bootstrap.bundle.min.js') }}"></script>
        <script src="{{ \App\Support\FrontendAssets::url('jquery.dataTables.min.js') }}"></script>
        <script src="{{ \App\Support\FrontendAssets::url('dataTables.bootstrap5.min.js') }}"></script>
        <script src="{{ \App\Support\FrontendAssets::url('toastr.min.js') }}"></script>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.jQuery || !jQuery.fn.DataTable) return;
            jQuery('.datatable').each(function () {
                if (!jQuery.fn.DataTable.isDataTable(this)) jQuery(this).DataTable({ language: { searchPlaceholder: 'Search…' } });
            });
        });
        </script>
    @endif
    @stack('scripts')
    @livewireScripts
    @fluxScripts
</body>
</html>
