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
        'attendee_id',
        'token',
        'is_active',
        'is_used',
        'ticket_type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_used' => 'boolean'
    ];

    protected $appends = ['ticket_type'];

    /**
     * Get the event that owns the event card.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attendee that owns the event card.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * Get the scan logs for the event card.
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function getTicketTypeAttribute(): string
    {
        return $this->attendee->ticket_type;
    }

    /**
     * Check if the event card is valid.
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->is_used;
    }

    /**
     * Generate a PDF version of the event card.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('event-cards.pdf', ['card' => $this]);
        
        return $pdf->download('event-card-' . $this->attendee->name . '.pdf');
    }
} 