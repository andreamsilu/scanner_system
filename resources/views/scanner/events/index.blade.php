<x-app-layout>
    <x-page-header :title="auth()->user()->isAdmin() ? 'Choose an event' : 'Your events'" subtitle="Open an event to start welcoming guests at the door." :breadcrumbs="['Admissions' => null]">
        <x-slot name="actions">
            @if(session('selected_event_id'))
                <flux:button :href="route('scanner.scan.index')" icon="qr-code" variant="primary">Continue scanning</flux:button>
            @endif
        </x-slot>
    </x-page-header>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($events as $event)
            <article class="ui-panel p-5 sm:p-6 flex flex-col">
                <div class="flex justify-between items-center gap-3 mb-5"><span class="grid size-12 place-items-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300"><flux:icon.calendar-days class="size-6" /></span><flux:badge :color="$event->status === 'active' ? 'teal' : 'zinc'" size="sm">{{ ucfirst($event->status) }}</flux:badge></div>
                <h2 class="font-semibold text-lg mb-3">{{ $event->name }}</h2>
                <p class="ui-muted text-xs mb-2 flex items-center gap-2"><flux:icon.calendar class="size-4" />{{ $event->start_date->format('d M Y') }} – {{ $event->end_date->format('d M Y') }}</p>
                <p class="ui-muted text-xs mb-6 flex items-center gap-2"><flux:icon.map-pin class="size-4" />{{ $event->location }}</p>
                <div class="grid grid-cols-3 gap-3 py-4 border-y border-zinc-100 dark:border-zinc-700 mb-5 text-xs">
                    <div><span class="ui-muted block mb-2">Registered</span><strong class="text-base">{{ $event->scan_stats['total_attendees'] }}</strong></div>
                    <div><span class="ui-muted block mb-2">Checked in</span><strong class="text-base text-teal-700 dark:text-teal-300">{{ $event->scan_stats['scanned_count'] }}</strong></div>
                    <div><span class="ui-muted block mb-2">Not arrived</span><strong class="text-base">{{ $event->scan_stats['pending_count'] }}</strong></div>
                </div>
                <form action="{{ route('scanner.events.select', $event) }}" method="POST" class="mt-auto">@csrf<flux:button type="submit" variant="primary" icon="qr-code" class="w-full">Open check-in</flux:button></form>
            </article>
        @empty
            <div class="ui-panel col-span-full text-center px-6 py-14"><flux:icon.calendar-days class="size-10 mx-auto text-zinc-400 mb-4" /><h2 class="font-semibold text-lg mb-2">{{ auth()->user()->isAdmin() ? 'No events yet' : 'No events assigned yet' }}</h2><p class="ui-muted text-sm mb-0">{{ auth()->user()->isAdmin() ? 'Create an event to prepare your welcome desk.' : 'Contact your administrator to get your event assignment.' }}</p></div>
        @endforelse
    </div>
    @if($events->hasPages())<div class="mt-6">{{ $events->links() }}</div>@endif
</x-app-layout>
