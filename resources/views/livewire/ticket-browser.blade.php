<div>
    <div class="flex flex-wrap justify-end gap-2 mb-6">
        <flux:modal.trigger name="registration-link"><flux:button icon="link">Registration link</flux:button></flux:modal.trigger>
        <flux:button icon="arrow-up-tray" :href="route('events.attendees.bulk-upload', $event)">Import guests</flux:button>
        <flux:button variant="primary" icon="user-plus" wire:click="newGuest">Add guest</flux:button>
    </div>
    @if($notice)<p class="scan-result mb-5 text-sm" data-status="success" role="status">{{ $notice }}</p>@endif
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Total guests</span><strong>{{ number_format($summary['guests']) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Tickets issued</span><strong>{{ number_format($summary['tickets']) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">People admitted</span><strong class="text-teal-700 dark:text-teal-300">{{ number_format($summary['admissions']) }}</strong></div>
        <div class="ui-panel ui-kpi"><span class="text-xs ui-muted">Guests without tickets</span><strong>{{ number_format($summary['without_tickets']) }}</strong></div>
    </div>
    <section class="ui-panel" aria-label="Guests and tickets">
        <div class="p-4 sm:p-6 border-b border-zinc-100 dark:border-zinc-700">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" label="Find a guest" placeholder="Name, phone, email or ticket number" type="search" maxlength="200" />
                <flux:select wire:model.live="status" label="Entry status">
                    <option value="">All statuses</option><option value="active">Available ticket</option>
                    <option value="partial">Partially used</option><option value="used">Fully used</option>
                    <option value="checked_in">Checked in</option><option value="no_ticket">No ticket</option><option value="cancelled">Cancelled</option>
                </flux:select>
                <flux:select wire:model.live="type" label="Ticket type">
                    <option value="">All types</option><option value="single">Single</option><option value="double">Double</option>
                    <option value="vip">VIP</option><option value="regular">Regular</option>
                </flux:select>
                <flux:select wire:model.live="sort" label="Order">
                    <option value="asc">Ticket number ↑</option><option value="desc">Ticket number ↓</option>
                </flux:select>
            </div>
            <div class="flex justify-between items-center mt-4 gap-3 text-xs ui-muted">
                <span role="status" aria-live="polite"><span wire:loading.remove>{{ number_format($guests->total()) }} {{ $guests->total() === 1 ? 'guest' : 'guests' }} found</span><span wire:loading>Updating guests…</span></span>
                @if($search || $status || $type)<flux:button wire:click="resetFilters" variant="subtle" size="sm">Clear filters</flux:button>@endif
            </div>
        </div>
        <div class="hidden md:block overflow-x-auto" wire:loading.class="opacity-50" tabindex="0" role="region" aria-label="Scrollable guest list">
            <table class="ui-table">
                <thead><tr><th scope="col">Guest</th><th scope="col">Ticket</th><th scope="col">Admission</th><th scope="col">Last scan</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach($guests as $guest)
                        <tr wire:key="guest-row-{{ $guest->id }}">
                            <td><p class="font-semibold mb-1">{{ $guest->name }}</p><p class="ui-muted text-xs mb-1">{{ $guest->phone ?: 'No phone number' }}</p>@if($guest->email)<p class="ui-muted text-xs mb-0">{{ $guest->email }}</p>@endif</td>
                            <td>@if($guest->ticket)<p class="font-mono font-semibold mb-1">#{{ $guest->ticket->ticket_number }}</p><span class="text-xs ui-muted">{{ $guest->ticket->type === 'vip' ? 'VIP' : ucfirst($guest->ticket->type) }}</span>@else<flux:badge size="sm" color="zinc">No ticket</flux:badge>@endif</td>
                            <td><x-guest-admission :guest="$guest" /></td>
                            <td class="ui-muted text-xs">{{ $guest->ticket?->scan_logs_max_created_at ? \Illuminate\Support\Carbon::parse($guest->ticket->scan_logs_max_created_at)->diffForHumans() : 'No scans yet' }}</td>
                            <td><flux:button wire:click="editGuest({{ $guest->id }})" variant="subtle" size="sm" aria-label="Manage {{ $guest->name }}">Manage guest</flux:button>@if(!$guest->ticket && $guest->registration_status !== 'cancelled')<flux:button wire:click="editGuest({{ $guest->id }}, true)" size="sm">Issue ticket</flux:button>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="md:hidden divide-y divide-zinc-100 dark:divide-zinc-700" wire:loading.class="opacity-50">
            @foreach($guests as $guest)
                <article class="p-4 space-y-3" wire:key="guest-card-{{ $guest->id }}" aria-label="{{ $guest->name }}">
                    <div><h2 class="font-semibold">{{ $guest->name }}</h2><p class="ui-muted text-sm break-all">{{ $guest->phone }}</p>@if($guest->email)<p class="ui-muted text-xs break-all">{{ $guest->email }}</p>@endif</div>
                    <div class="flex flex-wrap justify-between gap-3"><p class="text-sm">{{ $guest->ticket ? '#'.$guest->ticket->ticket_number.' · '.($guest->ticket->type === 'vip' ? 'VIP' : ucfirst($guest->ticket->type)) : 'No ticket' }}</p><x-guest-admission :guest="$guest" /></div>
                    <div class="flex flex-wrap gap-2"><flux:button wire:click="editGuest({{ $guest->id }})" size="sm">Manage guest</flux:button>@if(!$guest->ticket && $guest->registration_status !== 'cancelled')<flux:button wire:click="editGuest({{ $guest->id }}, true)" size="sm">Issue ticket</flux:button>@endif</div>
                </article>
            @endforeach
        </div>
        @if($guests->isEmpty())
            <div class="text-center px-4 py-14"><flux:icon.users class="size-9 mx-auto text-zinc-400 mb-3" />
                <h2 class="font-semibold mb-2">{{ $search || $status || $type ? 'No matching guests' : 'No guests yet' }}</h2>
                <p class="ui-muted text-sm mb-4">{{ $search || $status || $type ? 'Try another name or adjust the filters.' : 'Add guests, import a list or share your registration form.' }}</p>
                @if($search || $status || $type)<flux:button wire:click="resetFilters" size="sm">Clear filters</flux:button>@else<flux:button wire:click="newGuest" size="sm">Add your first guest</flux:button>@endif
            </div>
        @endif
        <nav aria-label="Guest pagination" class="flex flex-wrap items-center justify-between gap-4 p-4 sm:px-6 border-t border-zinc-100 dark:border-zinc-700">
            <p class="text-xs ui-muted">Showing {{ $guests->firstItem() ?? 0 }}–{{ $guests->lastItem() ?? 0 }} of {{ number_format($guests->total()) }} guests</p>
            @if($guests->hasPages())<div class="flex items-center gap-3"><flux:button wire:click="previousPage" :disabled="$guests->onFirstPage()" size="sm">Previous</flux:button><span class="text-xs ui-muted">{{ $guests->currentPage() }} / {{ $guests->lastPage() }}</span><flux:button wire:click="nextPage" :disabled="!$guests->hasMorePages()" size="sm">Next</flux:button></div>@endif
        </nav>
    </section>

    <flux:modal name="guest-details" variant="flyout" class="w-full sm:max-w-lg">
        <h2 class="text-xl font-semibold mb-2">{{ $selectedGuestId ? 'Guest details & ticket' : 'Add guest' }}</h2>
        <p class="ui-muted text-sm mb-6">{{ $selectedGuestId ? 'Update contact details and manage this guest’s entry.' : 'Add a guest and optionally issue their ticket.' }}</p>
        <form wire:submit="saveGuest" class="space-y-5">
            <flux:input wire:model="name" label="Full name" required maxlength="255" />
            <flux:input wire:model="phone" label="Phone number" type="tel" required maxlength="20" />
            <flux:input wire:model="email" label="Email (optional)" type="email" maxlength="255" />
            <flux:input wire:model="position" label="Position / organization (optional)" maxlength="255" />
            @if(!$selectedGuest?->ticket && $selectedGuest?->registration_status !== 'cancelled')
                <flux:checkbox wire:model.live="createTicket" label="Issue a ticket" />
                @if($createTicket)<flux:select wire:model="ticketType" label="Ticket type"><option value="single">Single — 1 entry</option><option value="double">Double — 2 entries</option><option value="regular">Regular — 1 entry</option><option value="vip">VIP — 1 entry</option></flux:select>@endif
            @endif
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button">Close</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ $selectedGuestId ? 'Save guest' : 'Add guest' }}</flux:button></div>
        </form>
        @if($selectedGuest?->ticket)
            <section class="border-t border-zinc-200 dark:border-zinc-700 mt-6 pt-6 space-y-4" aria-label="Guest ticket">
                <div class="flex justify-between gap-3"><h3 class="font-semibold">Ticket #{{ $selectedGuest->ticket->ticket_number }}</h3><span class="ui-muted text-sm">{{ ucfirst($selectedGuest->ticket->type) }}</span></div>
                <x-guest-admission :guest="$selectedGuest" />
                @if($selectedGuest->qr_code)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($selectedGuest->qr_code) }}" width="200" height="200" class="bg-white p-3 rounded-xl mx-auto" alt="Admission QR for {{ $selectedGuest->name }}" />@endif
                <x-guest-ticket-actions :event="$event" :ticket="$selectedGuest->ticket" :guest="$selectedGuest" />
            </section>
        @endif
        @if($selectedGuest && $selectedGuest->registration_status !== 'cancelled')
            <div class="border-t border-zinc-200 dark:border-zinc-700 mt-6 pt-6"><flux:button wire:click="cancelGuest({{ $selectedGuest->id }})" wire:confirm="Cancel this guest’s registration and tickets? They will no longer be admitted. Admission history will be kept." variant="danger" size="sm">Cancel registration</flux:button></div>
        @endif
    </flux:modal>
    <flux:modal name="registration-link" class="w-full sm:max-w-lg">
        <h2 class="text-xl font-semibold mb-3">Public registration link</h2>
        <p class="ui-muted text-sm mb-5">Guests can register without an account and receive a single-entry QR ticket.</p>
        @if($event->public_registration_token)
            <p class="text-sm mb-4">Registration is <strong>{{ $event->public_registration_enabled ? 'enabled' : 'disabled' }}</strong>.</p>
            <div x-data="{ copied: false }" class="space-y-3 mb-5">
                <flux:input label="Shareable link" readonly :value="route('public-registration.show', $event->public_registration_token)" x-ref="registrationLink" x-on:click="$el.select()" />
                <flux:button x-on:click="(async () => { try { await navigator.clipboard.writeText($refs.registrationLink.value); copied = true; } catch (e) { window.prompt('Copy registration link', $refs.registrationLink.value); } })()"><span x-text="copied ? 'Copied!' : 'Copy link'">Copy link</span></flux:button>
                @if($event->public_registration_enabled)<flux:button :href="route('public-registration.show', $event->public_registration_token)" target="_blank">Open form</flux:button>@endif
            </div>
        @endif
        <form method="POST" action="{{ route('events.public-registration', $event) }}" class="flex flex-wrap gap-2">
            @csrf <input type="hidden" name="return_to" value="guests">
            @if(!$event->public_registration_token)<flux:button type="submit" name="action" value="generate" variant="primary">Generate registration link</flux:button>
            @else
                <flux:button type="submit" name="action" :value="$event->public_registration_enabled ? 'disable' : 'enable'">{{ $event->public_registration_enabled ? 'Disable registration' : 'Enable registration' }}</flux:button>
                <flux:button type="submit" name="action" value="regenerate" onclick="return confirm('Replace this link? The previous link will stop working.');">Replace link</flux:button>
            @endif
        </form>
    </flux:modal>
</div>
