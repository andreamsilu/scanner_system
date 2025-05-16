<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scanner_id',
        'ticket_id',
        'scanned_at',
        'status',
        'notes'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the scanner (user) that performed the scan.
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }

    /**
     * Get the ticket that was scanned.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the event through the ticket.
     */
    public function event()
    {
        return $this->ticket->event();
    }

    /**
     * Get the attendee through the ticket.
     */
    public function attendee()
    {
        return $this->ticket->attendee();
    }

    /**
     * Check if the scan was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the scan was invalid.
     */
    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    /**
     * Check if the scan was duplicate.
     */
    public function isDuplicate(): bool
    {
        return $this->status === 'duplicate';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($scanLog) {
            if (empty($scanLog->scanned_at)) {
                $scanLog->scanned_at = now();
            }
        });
    }
} 