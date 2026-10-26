<x-public-event-layout :event="$event" title="Registration confirmed">
    <h2 class="text-2xl font-semibold mb-3">You're registered!</h2>
    <p class="ui-muted mb-6">{{ $ticket->attendee->name }}, save your ticket and show its QR code at the entrance.</p>
    <div class="text-center border border-zinc-200 dark:border-zinc-700 rounded-xl p-5">
        <p class="ui-muted text-sm">Single-entry ticket</p>
        <p class="text-4xl font-semibold my-3">#{{ $ticket->ticket_number }}</p>
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->attendee->qr_code) }}" alt="Your admission QR code" width="240" height="240" class="mx-auto bg-white rounded-lg p-3" />
    </div>
    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->attendee->qr_code) }}" download="event-ticket-{{ $ticket->ticket_number }}.svg" class="block text-center font-semibold text-teal-700 dark:text-teal-300 mt-6">Download QR ticket</a>
    <p class="ui-muted text-xs mt-5">Keep your QR code private. Each ticket admits one person once.</p>
</x-public-event-layout>
