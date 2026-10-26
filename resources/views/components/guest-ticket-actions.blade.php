@props(['event', 'ticket', 'guest'])
@php
    $token = $guest->token;
    $publicUrl = route('tickets.verify', $token);
    $shareText = $guest->name.', your ticket #'.$ticket->ticket_number.' for '.$event->name.': '.$publicUrl;
@endphp
<div class="flex flex-wrap gap-2">
    <flux:button :href="route('events.tickets.print', [$event, $token])" target="_blank" icon="printer" size="sm">Print ticket</flux:button>
    <flux:dropdown>
        <flux:button icon:trailing="chevron-down" size="sm">Ticket actions</flux:button>
        <flux:menu>
            <flux:menu.item icon="arrow-down-tray" :href="route('events.tickets.export-ticket', [$event, $token, 'format' => 'pdf'])">Download PDF</flux:menu.item>
            <flux:menu.item icon="photo" :href="route('events.tickets.download-jpg', [$event, $token])">Download image</flux:menu.item>
            @if($guest->phone)<flux:menu.item icon="chat-bubble-left-right" :data-invitation="json_encode(['id' => $ticket->id, 'attendeeId' => $guest->id, 'name' => $guest->name, 'phone' => $guest->phone, 'number' => $ticket->ticket_number, 'url' => $publicUrl])" x-on:click="$dispatch('open-ticket-invitation', JSON.parse($el.dataset.invitation))">Send invitation SMS</flux:menu.item>@endif
            <flux:menu.separator />
            <flux:menu.item icon="share" :href="'https://wa.me/?text='.rawurlencode($shareText)" target="_blank" rel="noopener">Share on WhatsApp</flux:menu.item>
            <flux:menu.item icon="device-phone-mobile" :href="'sms:?body='.rawurlencode($shareText)">Share by SMS</flux:menu.item>
            <flux:menu.item icon="link" :data-ticket-url="$publicUrl" x-data="{ copied: false }" x-on:click="(async () => { try { await navigator.clipboard.writeText($el.dataset.ticketUrl); copied = true; } catch (e) { window.prompt('Copy ticket link', $el.dataset.ticketUrl); } })()"><span x-text="copied ? 'Link copied' : 'Copy ticket link'">Copy ticket link</span></flux:menu.item>
            @if($ticket->status === 'active')
                <flux:menu.separator />
                <form action="{{ route('events.tickets.deactivate', [$event, $token]) }}" method="POST" onsubmit="return confirm('Deactivate this ticket? It will no longer admit guests.');">
                    @csrf @method('PATCH') <flux:menu.item type="submit" variant="danger" icon="no-symbol">Deactivate ticket</flux:menu.item>
                </form>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
