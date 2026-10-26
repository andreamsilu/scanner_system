<x-app-layout>
    <x-page-header title="Overview" :subtitle="'Welcome back, '.auth()->user()->name.'. Here is what is happening across your events.'" :breadcrumbs="['Overview' => null]">
        <x-slot name="actions"><flux:button variant="primary" icon="plus" :href="route('events.create')">Create event</flux:button></x-slot>
    </x-page-header>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
        @foreach([
            ['label' => 'Total events', 'value' => $stats['total_events'], 'icon' => 'calendar-days'],
            ['label' => 'Upcoming events', 'value' => $stats['upcoming_events'], 'icon' => 'arrow-up-right'],
            ['label' => 'Completed events', 'value' => $stats['completed_events'], 'icon' => 'check-circle'],
            ['label' => 'Assigned scanners · live events', 'value' => $stats['active_scanners'], 'icon' => 'qr-code'],
        ] as $metric)
            <div class="ui-panel ui-kpi"><div class="flex items-center justify-between gap-2"><span class="text-xs ui-muted">{{ $metric['label'] }}</span><flux:icon :name="$metric['icon']" class="size-4 text-teal-600" /></div><strong>{{ number_format($metric['value']) }}</strong></div>
        @endforeach
    </div>
    <section class="mb-6 p-6 sm:p-8 bg-teal-950 rounded-2xl text-white flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div><p class="text-teal-300 text-xs font-semibold uppercase tracking-widest mb-2">Make a great first impression</p><h2 class="text-xl font-semibold tracking-tight mb-2">Your next guest is one scan away.</h2><p class="text-sm text-teal-100/80 mb-0">Select an event to open the scanner and start welcoming guests.</p></div>
        <flux:button :href="route('scanner.events.index')" icon="qr-code" class="shrink-0 bg-white! text-teal-950! border-transparent!">Open check-in</flux:button>
    </section>
    <div class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
        <section class="ui-panel">
            <div class="ui-panel-heading"><h2 class="text-base font-semibold mb-0">Recent activity</h2><flux:button :href="route('reports.index')" variant="subtle" size="sm" icon:trailing="arrow-up-right">Reports</flux:button></div>
            <div class="overflow-x-auto">
                <table class="ui-table"><thead><tr><th scope="col">Guest / event</th><th scope="col">Scanner</th><th scope="col">Status</th><th scope="col">Time</th></tr></thead><tbody>
                    @forelse($recent_activity as $activity)
                        <tr><td><p class="font-medium mb-1">{{ $activity['attendee'] }}</p><p class="text-xs ui-muted mb-0">{{ $activity['event'] }}</p></td><td class="text-xs ui-muted">{{ $activity['scanner'] }}</td><td><flux:badge :color="$activity['status'] === 'success' ? 'teal' : 'amber'" size="sm">{{ ucfirst($activity['status']) }}</flux:badge></td><td class="text-xs ui-muted">{{ $activity['time'] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center! py-14!"><flux:icon.clock class="size-9 mx-auto mb-3 text-zinc-400" /><p class="font-medium mb-1">All set for your first arrival</p><p class="ui-muted text-sm mb-0">Admissions will appear here as guests check in.</p></td></tr>
                    @endforelse
                </tbody></table>
            </div>
        </section>
        <div class="space-y-6">
            <section class="ui-panel"><div class="ui-panel-heading"><h2 class="text-base font-semibold mb-0">Coming up</h2><flux:icon.calendar-days class="size-4 ui-muted" /></div>
                <div class="px-5 py-2">
                    @forelse($upcoming_events as $event)
                        <a href="{{ route('events.show', $event) }}" class="flex items-center gap-3 py-4 no-underline text-inherit border-b border-zinc-100 last:border-0 dark:border-zinc-700">
                            <span class="flex flex-col items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-700 size-12 shrink-0"><span class="text-[10px] uppercase ui-muted">{{ $event->start_date->format('M') }}</span><span class="text-lg font-semibold">{{ $event->start_date->format('d') }}</span></span>
                            <span class="min-w-0"><span class="block font-medium text-sm truncate">{{ $event->name }}</span><span class="ui-muted text-xs">{{ $event->location }}</span></span>
                        </a>
                    @empty<p class="ui-muted text-sm py-6 mb-0">No upcoming events. Create one when you are ready.</p>@endforelse
                </div>
            </section>
            <section class="ui-panel"><div class="ui-panel-heading"><h2 class="text-base font-semibold mb-0">Scanner activity today</h2></div><div class="px-5 py-2">
                @forelse($scanner_performance as $scanner)<div class="flex justify-between gap-3 items-center py-3"><span class="text-sm">{{ $scanner->name }}</span><flux:badge color="teal" size="sm">{{ $scanner->scan_logs_count }} scans</flux:badge></div>
                @empty<p class="ui-muted text-sm py-6 mb-0">No scanner activity yet today.</p>@endforelse
            </div></section>
        </div>
    </div>
</x-app-layout>
