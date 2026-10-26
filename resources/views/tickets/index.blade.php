<x-app-layout>
    <x-page-header title="Tickets" :subtitle="$event->name.' · Manage invitations and admissions'" :breadcrumbs="['Events' => route('events.index'), $event->name => route('events.show', $event), 'Tickets' => null]">
        <x-slot name="actions">
            <flux:dropdown>
                <flux:button icon="arrow-down-tray" icon:trailing="chevron-down">Export tickets</flux:button>
                <flux:menu>
                    <flux:menu.item :href="route('events.tickets.export', ['event' => $event, 'format' => 'excel'])">Excel spreadsheet</flux:menu.item>
                    <flux:menu.item :href="route('events.tickets.export', ['event' => $event, 'format' => 'pdf'])">PDF document</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button variant="primary" icon="users" :href="route('events.attendees.index', $event)">Manage attendees</flux:button>
        </x-slot>
    </x-page-header>
    <livewire:ticket-browser :event="$event" />

    <div x-data="ticketInvitation(@js(route('sms.management.send-attendee-invitation', $event)))" x-on:open-ticket-invitation.window="open($event.detail)">
        <dialog x-ref="dialog" class="ui-dialog" aria-labelledby="invitation-title" x-on:cancel="if (sending) $event.preventDefault()">
            <h2 id="invitation-title" class="text-xl font-semibold mb-2">Send an invitation</h2>
            <p class="ui-muted text-sm mb-5">Send this guest their event details and ticket link by SMS.</p>
            <dl class="text-sm space-y-3 mb-6">
                <div class="flex justify-between gap-4"><dt class="ui-muted">Guest</dt><dd class="font-medium text-right mb-0" x-text="ticket?.name"></dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">Phone</dt><dd class="mb-0" x-text="ticket?.phone"></dd></div>
                <div class="flex justify-between gap-4"><dt class="ui-muted">Ticket</dt><dd class="mb-0" x-text="'#' + (ticket?.number ?? '')"></dd></div>
            </dl>
            <p x-show="message" x-cloak class="scan-result text-sm mb-4" :data-status="failed ? 'danger' : 'success'" role="status" x-text="message"></p>
            <div class="flex justify-end gap-2">
                <flux:button x-on:click="$refs.dialog.close()" x-bind:disabled="sending">Close</flux:button>
                <flux:button variant="primary" x-on:click="send()" x-bind:disabled="sending || (!!message && !failed)"><span x-text="sending ? 'Sending…' : 'Send SMS'">Send SMS</span></flux:button>
            </div>
        </dialog>
    </div>
</x-app-layout>
