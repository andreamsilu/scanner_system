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
        'attendee_id',
        'ticket_id',
        'scanned_at'
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the scanner that performed the scan.
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(Scanner::class);
    }

    /**
     * Get the attendee that was scanned.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
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
} 