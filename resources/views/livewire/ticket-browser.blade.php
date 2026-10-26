<div>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Total tickets</span><strong>{{ number_format($summary->total) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Available tickets</span><strong>{{ number_format($summary->active ?? 0) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">People admitted</span><strong class="text-teal-700 dark:text-teal-300">{{ number_format($summary->admissions) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Double tickets</span><strong>{{ number_format($summary->doubles ?? 0) }}</strong></div>
    </div>

    <section class="ui-panel" aria-label="Event tickets">
        <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-zinc-700">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_160px_160px_170px]">
                <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" label="Find a ticket" placeholder="Name, phone or ticket number" type="search" maxlength="200" />
                <flux:select wire:model.live="status" label="Status">
                    <option value="">All statuses</option>
                    <option value="active">Available</option>
                    <option value="partial">Partially used</option>
                    <option value="used">Fully used</option>
                    <option value="cancelled">Cancelled</option>
                </flux:select>
                <flux:select wire:model.live="type" label="Ticket type">
                    <option value="">All types</option>
                    <option value="single">Single</option><option value="double">Double</option>
                    <option value="vip">VIP</option><option value="regular">Regular</option>
                </flux:select>
                <flux:select wire:model.live="sort" label="Order">
                    <option value="asc">Ticket number ↑</option>
                    <option value="desc">Ticket number ↓</option>
                </flux:select>
            </div>
            <div class="flex justify-between items-center mt-4 gap-3 text-xs ui-muted">
                <span role="status" aria-live="polite"><span wire:loading.remove>{{ number_format($tickets->total()) }} {{ $tickets->total() === 1 ? 'ticket' : 'tickets' }} found</span><span wire:loading>Updating tickets…</span></span>
                @if($search || $status || $type)<flux:button wire:click="resetFilters" variant="subtle" size="sm">Clear filters</flux:button>@endif
            </div>
        </div>
        <div class="overflow-x-auto" wire:loading.class="opacity-50" tabindex="0" role="region" aria-label="Scrollable ticket list">
            <table class="ui-table">
                <thead><tr><th scope="col">Ticket / attendee</th><th scope="col">Type</th><th scope="col">Admission</th><th scope="col">Last scan</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        @php
                            $token = $ticket->attendee?->token;
                            $publicUrl = $token ? url('/tickets/'.$token.'/verify') : null;
                            $shareText = $ticket->attendee?->name.', your ticket #'.$ticket->ticket_number.' for '.$event->name.': '.$publicUrl;
                        @endphp
                        <tr wire:key="ticket-{{ $ticket->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-zinc-100 dark:bg-zinc-700 font-mono text-xs">#{{ $ticket->ticket_number }}</span>
                                    <div><p class="font-semibold mb-0.5">{{ $ticket->attendee?->name ?? 'Unassigned ticket' }}</p><p class="ui-muted text-xs mb-0">{{ $ticket->attendee?->phone ?? 'No phone number' }}</p></div>
                                </div>
                            </td>
                            <td><flux:badge :color="$ticket->type === 'double' ? 'violet' : 'zinc'" size="sm">{{ $ticket->type === 'vip' ? 'VIP' : ucfirst($ticket->type) }}</flux:badge><p class="ui-muted text-xs mt-1 mb-0">{{ $ticket->entry_limit }} {{ $ticket->entry_limit === 1 ? 'person' : 'people' }}</p></td>
                            <td>
                                <flux:badge :color="match($ticket->status) { 'cancelled' => 'red', 'used' => 'zinc', default => $ticket->entries_used ? 'amber' : 'teal' }" size="sm">{{ match($ticket->status) { 'cancelled' => 'Cancelled', 'used' => 'Fully used', default => $ticket->entries_used ? 'Partially used' : 'Available' } }}</flux:badge>
                                <p class="ui-muted text-xs mt-1 mb-0">{{ $ticket->entries_used }} of {{ $ticket->entry_limit }} admitted</p>
                            </td>
                            <td class="ui-muted text-xs">{{ $ticket->scan_logs_max_created_at ? \Illuminate\Support\Carbon::parse($ticket->scan_logs_max_created_at)->diffForHumans() : 'No scans yet' }}</td>
                            <td>
                                @if($token)
                                    <div class="flex justify-end items-center gap-1">
                                        <flux:button :href="route('events.tickets.show', [$event, $token])" variant="subtle" size="sm">View ticket</flux:button>
                                        <flux:dropdown>
                                            <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" aria-label="Actions for ticket #{{ $ticket->ticket_number }}" />
                                            <flux:menu>
                                                <flux:menu.item icon="printer" :href="route('events.tickets.print', [$event, $token])" target="_blank">Print ticket</flux:menu.item>
                                                <flux:menu.item icon="arrow-down-tray" :href="route('events.tickets.export-ticket', [$event, $token, 'format' => 'pdf'])">Download PDF</flux:menu.item>
                                                <flux:menu.item icon="photo" :href="route('events.tickets.download-jpg', [$event, $token])">Download image</flux:menu.item>
                                                @if($ticket->attendee?->phone)
                                                    <flux:menu.item icon="chat-bubble-left-right" :data-invitation="json_encode(['id' => $ticket->id, 'attendeeId' => $ticket->attendee_id, 'name' => $ticket->attendee->name, 'phone' => $ticket->attendee->phone, 'number' => $ticket->ticket_number, 'url' => $publicUrl])" x-on:click="$dispatch('open-ticket-invitation', JSON.parse($el.dataset.invitation))">Send invitation SMS</flux:menu.item>
                                                @endif
                                                <flux:menu.separator />
                                                <flux:menu.item icon="share" :href="'https://wa.me/?text='.rawurlencode($shareText)" target="_blank" rel="noopener">Share on WhatsApp</flux:menu.item>
                                                <flux:menu.item icon="device-phone-mobile" :href="'sms:?body='.rawurlencode($shareText)">Share by SMS</flux:menu.item>
                                                <flux:menu.item icon="link" :data-ticket-url="$publicUrl" x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText($el.dataset.ticketUrl).then(() => copied = true).catch(() => window.prompt('Copy this ticket link', $el.dataset.ticketUrl))"><span x-text="copied ? 'Link copied' : 'Copy ticket link'">Copy ticket link</span></flux:menu.item>
                                                @if($ticket->status === 'active')
                                                    <flux:menu.separator />
                                                    <form action="{{ route('events.tickets.deactivate', [$event, $token]) }}" method="POST" onsubmit="return confirm('Deactivate this ticket? It will no longer admit guests.');">
                                                        @csrf @method('PATCH')
                                                        <flux:menu.item type="submit" icon="no-symbol" variant="danger">Deactivate ticket</flux:menu.item>
                                                    </form>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                @else
                                    <span class="text-xs ui-muted">Assign an attendee first</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center! py-14! whitespace-normal">
                            <flux:icon.ticket class="size-9 mx-auto text-zinc-400 mb-3" />
                            <h2 class="text-base font-semibold mb-2">{{ $search || $status || $type ? 'No matching tickets' : 'No tickets yet' }}</h2>
                            <p class="ui-muted text-sm mb-4">{{ $search || $status || $type ? 'Try another name or adjust the filters.' : 'Add attendees to this event to issue their tickets.' }}</p>
                            @if($search || $status || $type)<flux:button wire:click="resetFilters" size="sm">Clear filters</flux:button>
                            @else<flux:button :href="route('events.attendees.index', $event)" size="sm">Manage attendees</flux:button>@endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <nav aria-label="Ticket pagination" class="flex flex-wrap items-center justify-between gap-4 px-4 sm:px-6 py-4 border-t border-zinc-100 dark:border-zinc-700">
            <p class="text-xs ui-muted mb-0">Showing {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} of {{ number_format($tickets->total()) }} tickets</p>
            @if($tickets->hasPages())
                <div class="flex items-center gap-3">
                    <flux:button wire:click="previousPage" :disabled="$tickets->onFirstPage()" size="sm" aria-label="Previous ticket page">Previous</flux:button>
                    <span class="text-xs ui-muted">{{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span>
                    <flux:button wire:click="nextPage" :disabled="! $tickets->hasMorePages()" size="sm" aria-label="Next ticket page">Next</flux:button>
                </div>
            @endif
        </nav>
    </section>
</div>
