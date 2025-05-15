<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'card_number',
        'status',
        'issued_at',
        'issued_to'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the event that owns the card.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attendee that the card was issued to.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class, 'issued_to');
    }

    /**
     * Get the scan logs for this card.
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Check if the card is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the card is assigned to an attendee.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->issued_to);
    }
} 