<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'attendee_id',
        'event_card_id',
        'scanner_id',
        'status',
        'notes',
        'scan_time'
    ];

    protected $casts = [
        'scan_time' => 'datetime'
    ];

    /**
     * Get the event that owns the scan log.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attendee that owns the scan log.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * Get the event card that owns the scan log.
     */
    public function eventCard(): BelongsTo
    {
        return $this->belongsTo(EventCard::class);
    }

    /**
     * Get the scanner that owns the scan log.
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
} 