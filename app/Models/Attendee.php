<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'event_id',
        'ticket_id',
        'registration_status',
        'registered_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'registered_at' => 'datetime'
    ];

    /**
     * Get the events that the attendee is registered for.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('registration_status', 'registered_at')
            ->withTimestamps();
    }

    /**
     * Get the ticket associated with the attendee.
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    /**
     * Get the scans associated with the attendee's tickets.
     */
    public function scans(): HasMany
    {
        return $this->hasManyThrough(Scan::class, Ticket::class);
    }

    /**
     * Scope a query to only include active attendees.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('events', function ($query) {
            $query->where('status', 'active');
        });
    }

    /**
     * Check if the attendee is registered for a specific event.
     */
    public function isRegisteredFor(Event $event): bool
    {
        return $this->events()->where('event_id', $event->id)->exists();
    }

    /**
     * Get the registration status for a specific event.
     */
    public function getRegistrationStatus(Event $event): ?string
    {
        $registration = $this->events()
            ->where('event_id', $event->id)
            ->first();

        return $registration ? $registration->pivot->status : null;
    }

    /**
     * Get the registration date for a specific event.
     */
    public function getRegistrationDate(Event $event): ?string
    {
        $registration = $this->events()
            ->where('event_id', $event->id)
            ->first();

        return $registration ? $registration->pivot->registered_at : null;
    }

    /**
     * Check if the attendee has checked in.
     */
    public function isCheckedIn(): bool
    {
        return $this->registration_status === 'checked_in';
    }

    /**
     * Check if the attendee's registration is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->registration_status === 'cancelled';
    }

    /**
     * Mark the attendee as checked in.
     */
    public function checkIn(): void
    {
        $this->update(['registration_status' => 'checked_in']);
    }

    /**
     * Cancel the attendee's registration.
     */
    public function cancel(): void
    {
        $this->update(['registration_status' => 'cancelled']);
    }

    /**
     * Assign a ticket to the attendee.
     */
    public function assignTicket(Ticket $ticket): void
    {
        $this->update(['ticket_id' => $ticket->id]);
    }

    /**
     * Remove the ticket assignment from the attendee.
     */
    public function removeTicket(): void
    {
        $this->update(['ticket_id' => null]);
    }
} 