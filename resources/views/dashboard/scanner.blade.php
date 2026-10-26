<x-app-layout>
    <x-page-header title="Welcome desk" :subtitle="'Welcome back, '.auth()->user()->name.'. Choose an event and start welcoming guests.'" :breadcrumbs="['Overview' => null]">
        <x-slot name="actions"><flux:button :href="route('scanner.history')" icon="clock">Admission history</flux:button></x-slot>
    </x-page-header>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
        @foreach(["Today's scans" => $stats['today_scans'], 'Confirmed scans today' => $stats['valid_scans'], 'Unsuccessful scans today' => $stats['invalid_scans'], 'Assigned events' => $stats['total_events']] as $label => $value)
            <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">{{ $label }}</span><strong>{{ number_format($value) }}</strong></div>
        @endforeach
    </div>
    @if(session('selected_event_id'))
        <div class="rounded-2xl bg-teal-950 text-white p-6 mb-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4"><div><p class="text-teal-300 text-xs uppercase tracking-widest mb-2">Your welcome desk is ready</p><h2 class="text-xl font-semibold mb-0">Continue checking in your guests.</h2></div><flux:button :href="route('scanner.scan.index')" icon="qr-code" class="bg-white! text-teal-950! shrink-0">Open scanner</flux:button></div>
    @endif
    <section class="mb-6">
        <div class="flex justify-between items-center mb-4"><h2 class="text-base font-semibold mb-0">Your assigned events</h2><flux:button :href="route('scanner.events.index')" variant="subtle" size="sm">View all</flux:button></div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($assigned_events as $event)
                <article class="ui-panel p-5 flex flex-col">
                    <div class="flex justify-between gap-3 mb-5"><span class="grid size-11 place-items-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300"><flux:icon.calendar-days class="size-6" /></span><flux:badge :color="$event->status === 'active' ? 'teal' : 'zinc'" size="sm">{{ ucfirst($event->status) }}</flux:badge></div>
                    <h3 class="text-base font-semibold mb-2">{{ $event->name }}</h3><p class="ui-muted text-xs mb-5">{{ $event->start_date->format('d M Y') }} · {{ $event->location }}</p>
                    <div class="flex gap-6 text-xs mb-5"><div><span class="ui-muted block mb-1">Registered</span><strong>{{ $event->scan_stats['total_attendees'] }}</strong></div><div><span class="ui-muted block mb-1">Checked in</span><strong>{{ $event->scan_stats['scanned_count'] }}</strong></div><div><span class="ui-muted block mb-1">Not arrived</span><strong>{{ $event->scan_stats['pending_count'] }}</strong></div></div>
                    <form action="{{ route('scanner.events.select', $event) }}" method="POST" class="mt-auto">@csrf<flux:button type="submit" variant="primary" icon="qr-code" class="w-full">Open check-in</flux:button></form>
                </article>
            @empty
                <div class="ui-panel col-span-full text-center py-12 px-6"><flux:icon.calendar-days class="size-9 mx-auto text-zinc-400 mb-4" /><h3 class="text-base font-semibold mb-2">No events assigned yet</h3><p class="ui-muted text-sm mb-0">Your administrator will assign an event before you begin scanning.</p></div>
            @endforelse
        </div>
    </section>
    <section class="ui-panel"><div class="ui-panel-heading"><h2 class="text-base font-semibold mb-0">Your recent activity</h2></div><div class="overflow-x-auto"><table class="ui-table"><thead><tr><th scope="col">Guest</th><th scope="col">Event</th><th scope="col">Status</th><th scope="col">Time</th></tr></thead><tbody>
        @forelse($recent_activity as $activity)<tr><td class="font-medium">{{ $activity['attendee'] }}</td><td class="ui-muted">{{ $activity['event'] }}</td><td><flux:badge :color="$activity['status'] === 'success' ? 'teal' : 'amber'" size="sm">{{ ucfirst($activity['status']) }}</flux:badge></td><td class="ui-muted text-xs">{{ $activity['time'] }}</td></tr>
        @empty<tr><td colspan="4" class="text-center! ui-muted py-10!">No admissions recorded yet.</td></tr>@endforelse
    </tbody></table></div></section>
</x-app-layout>
