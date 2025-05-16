<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'position'
    ];

    /**
     * Get the event that the attendee is registered for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the ticket associated with the attendee.
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    /**
     * Scope a query to only include active attendees (those with active tickets).
     */
    public function scopeActive($query)
    {
        return $query->whereHas('ticket', function ($query) {
            $query->where('status', 'active');
        });
    }

    /**
     * Check if the attendee has an active ticket.
     */
    public function hasActiveTicket(): bool
    {
        return $this->ticket && $this->ticket->status === 'active';
    }
} 