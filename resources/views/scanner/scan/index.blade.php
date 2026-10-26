<x-app-layout>
    <x-page-header title="Scan tickets" subtitle="A smooth welcome starts here." :breadcrumbs="['Admissions' => route('scanner.events.index'), 'Scan tickets' => null]">
        <x-slot name="actions"><flux:button icon="clock" :href="route('scanner.history')">Admission history</flux:button></x-slot>
    </x-page-header>
    <div id="scannerWorkspace" data-validate="{{ route('scanner.scan.validate') }}" data-start="{{ route('scanner.scan.start') }}" data-stop="{{ route('scanner.scan.stop') }}">
        <div class="ui-panel p-4 sm:p-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300"><flux:icon.calendar-days class="size-6" /></span>
                <div><p class="eyebrow mb-1">Selected event</p><h2 class="text-base font-semibold mb-1">{{ $event->name }}</h2><p class="text-xs ui-muted mb-0">{{ $event->start_date->format('d M Y') }} · {{ $event->location }}</p></div>
            </div>
            <form action="{{ route('scanner.events.deselect', $event) }}" method="POST">@csrf @method('DELETE')<flux:button type="submit" variant="subtle" icon="arrows-right-left">Change event</flux:button></form>
        </div>
        <div class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
            <section class="ui-panel" aria-label="Ticket scanner">
                <div class="ui-panel-heading"><h2 class="font-semibold mb-0">Camera check-in</h2><span class="text-xs ui-muted" id="scannerStatus" role="status">Camera paused</span></div>
                <div class="p-4 sm:p-6">
                    <div class="scan-stage mb-4">
                        <div id="cameraPlaceholder" class="scan-placeholder"><flux:icon.qr-code class="size-16! mx-auto mb-5 text-teal-300" /><p class="font-medium text-white mb-2">Ready for your next guest</p><p class="text-sm mb-0">Start your camera and scan their QR code.</p></div>
                        <div id="reader"></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mb-5">
                        <flux:button id="startCamera" variant="primary" class="min-h-12! w-full sm:w-auto">Start camera</flux:button>
                        <flux:button id="stopCamera" hidden class="min-h-12! w-full sm:w-auto">Pause camera</flux:button>
                        <span class="text-xs ui-muted">Use the rear camera for the clearest scan.</span>
                    </div>
                    <div id="scannerResult" class="scan-result mb-5" data-status="info" role="status" aria-live="polite" aria-atomic="true" hidden><h3 id="resultHeading" class="text-base font-semibold mb-1"></h3><p id="resultMessage" class="text-sm mb-0"></p></div>
                    <div class="border-t border-zinc-100 dark:border-zinc-700 pt-5">
                        <h2 class="text-sm font-semibold mb-1">Enter a ticket manually</h2><p class="text-xs ui-muted mb-4">Use the event ticket number or paste the ticket link.</p>
                        <form id="manualEntryForm" class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full"><flux:input id="manualToken" label="Ticket number or link" placeholder="e.g. 1" value="{{ request('ticket') }}" maxlength="2048" autocomplete="off" required /></div>
                            <flux:button id="manualSubmit" type="submit" variant="primary" class="min-h-10! w-full sm:w-auto">Check ticket</flux:button>
                        </form>
                    </div>
                </div>
            </section>
            <div class="space-y-5">
                <section class="ui-panel" aria-label="Recent admissions">
                    <div class="ui-panel-heading"><h2 class="font-semibold mb-0">Recent admissions</h2><flux:icon.clock class="size-4 ui-muted" /></div>
                    <div id="recentAdmissions" class="px-5 pb-2 max-h-[540px] overflow-y-auto">
                        @forelse($recent_scans as $scan)
                            <div class="recent-admission"><div class="flex justify-between gap-3"><h3>{{ $scan['attendee'] }}</h3><span class="text-xs {{ $scan['status_class'] === 'success' ? 'text-teal-700 dark:text-teal-300' : 'text-amber-700 dark:text-amber-300' }}">{{ ucfirst($scan['status']) }}</span></div><p>{{ $scan['time'] }} · {{ $scan['scanner'] }}</p></div>
                        @empty
                            <div id="recentEmpty" class="text-center py-10"><flux:icon.ticket class="size-8 mx-auto text-zinc-400 mb-3" /><p class="text-sm font-medium mb-1">Your first guest is next</p><p class="text-xs ui-muted mb-0">Confirmed entries will appear here.</p></div>
                        @endforelse
                    </div>
                </section>
                <aside class="rounded-2xl border border-teal-100 bg-teal-50 p-5 dark:border-teal-900 dark:bg-teal-950/40">
                    <div class="flex items-center gap-2 text-teal-800 dark:text-teal-200 mb-3"><flux:icon.user-group class="size-5" /><h2 class="text-sm font-semibold mb-0">One guest or two?</h2></div>
                    <p class="text-sm text-teal-900 dark:text-teal-100 mb-0 leading-relaxed">Double tickets ask how many people are entering. Choose <strong>Single entry</strong> for one guest now, or <strong>Double entry</strong> when both arrive together.</p>
                </aside>
            </div>
        </div>
    </div>
    <dialog id="entryChoiceDialog" class="ui-dialog" aria-labelledby="entryChoiceTitle" aria-describedby="entryChoiceRemaining">
        <span class="grid size-12 place-items-center rounded-xl bg-teal-50 text-teal-700 mb-5"><flux:icon.user-group class="size-6" /></span>
        <p class="eyebrow mb-2">Double ticket</p><h2 id="entryChoiceTitle" class="text-xl font-semibold mb-3">How many people are entering?</h2>
        <p id="entryChoiceName" class="font-medium mb-1"></p><p id="entryChoiceRemaining" class="ui-muted text-sm mb-6"></p>
        <div class="grid gap-3">
            <flux:button id="singleEntryButton" variant="primary" class="min-h-12! justify-start!">Single entry · 1 person</flux:button>
            <flux:button id="doubleEntryButton" class="min-h-12! justify-start!">Double entry · 2 people</flux:button>
            <flux:button id="cancelEntryButton" variant="subtle">Cancel</flux:button>
        </div>
    </dialog>
    @push('scripts')
        <script src="{{ asset('js/admission.js') }}"></script>
        @vite('resources/js/scanner.js')
    @endpush
</x-app-layout>
