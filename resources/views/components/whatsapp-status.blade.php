@props(['invitation' => null])
@if($invitation)
    <p class="text-xs ui-muted mt-2" role="status">WhatsApp: {{ match($invitation->status) {
        'accepted' => 'Accepted by Meta', 'unknown' => 'Delivery uncertain — check before resending',
        default => ucfirst($invitation->status),
    } }}</p>
@endif
