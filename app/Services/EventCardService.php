<?php

namespace App\Services;

use App\Models\EventCard;
use App\Models\Attendee;
use Illuminate\Support\Str;

class EventCardService
{
    public function createEventCard(array $data): EventCard
    {
        // Get the attendee to access their ticket type
        $attendee = Attendee::findOrFail($data['attendee_id']);
        
        // Generate a unique token
        $data['token'] = Str::random(32);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_used'] = false;

        // Create the event card
        return EventCard::create($data);
    }

    public function updateEventCard(EventCard $card, array $data): EventCard
    {
        $card->update($data);
        return $card;
    }

    public function deleteEventCard(EventCard $card): bool
    {
        return $card->delete();
    }

    public function markAsUsed(EventCard $card): EventCard
    {
        $card->update([
            'is_used' => true,
            'used_at' => now()
        ]);
        return $card;
    }
} 