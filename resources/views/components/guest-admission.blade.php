@props(['guest'])
@php
    $ticket = $guest->ticket;
    $cancelled = $guest->registration_status === 'cancelled' || $ticket?->status === 'cancelled';
    $label = $cancelled ? 'Cancelled' : ($ticket ? ($ticket->status === 'used' ? 'Fully used' : ($ticket->entries_used ? 'Partially used' : 'Available')) : ($guest->registration_status === 'checked_in' ? 'Checked in' : 'Not checked in'));
@endphp
<div>
    <flux:badge :color="$cancelled ? 'red' : ($ticket?->entries_used ? 'amber' : 'zinc')" size="sm">{{ $label }}</flux:badge>
    @if($ticket)<p class="ui-muted text-xs mt-1">{{ $ticket->entries_used }} of {{ $ticket->entry_limit }} entries used</p>@endif
</div>
