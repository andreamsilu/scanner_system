<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'attendee_id',
        'ticket_number',
        'type',
        'status',
        'token',
        'used_at',
        'cancelled_at'
    ];

    protected $casts = [
        'entries_used' => 'integer',
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the ticket ID (attendee's token)
     */
    public function getTicketIdAttribute()
    {
        return $this->ticket_number;
    }

    public function getEntryLimitAttribute(): int
    {
        return $this->type === 'double' ? 2 : 1;
    }

    public function getRemainingEntriesAttribute(): int
    {
        return max(0, $this->entry_limit - (int) $this->entries_used);
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            return parent::save($options);
        }
        return DB::transaction(function () use ($options) {
            $event = Event::whereKey($this->event_id)->lockForUpdate()->firstOrFail();
            $this->ticket_number = (string) $event->next_ticket_number;
            $event->next_ticket_number++;
            $event->saveQuietly();
            return parent::save($options);
        }, 3);
    }

    /**
     * Get the event that the ticket belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attendee that owns the ticket.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class)->withTrashed();
    }

    /**
     * Get the scan logs for this ticket.
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function whatsappInvitation()
    {
        return $this->hasOne(WhatsAppInvitation::class);
    }

    /**
     * Get the last scan for this ticket.
     */
    public function lastScan()
    {
        return $this->scanLogs()->latest()->first();
    }

    /**
     * Check if the ticket is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the ticket has been used.
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Check if the ticket has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark the ticket as used.
     */
    public function markAsUsed(): bool
    {
        throw new \LogicException('Use CheckInService to record admissions.');
    }

    /**
     * Mark the ticket as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    /**
     * Generate a unique ticket number.
     */

    /**
     * Generate QR code data for the ticket.
     */
    public function generateQrCode(): string
    {
        if (!$this->attendee) {
            throw new \Exception('Cannot generate QR code for unassigned ticket');
        }

        $data = [
            'token' => $this->attendee->token,
            'event_id' => $this->event_id
        ];

        return json_encode($data);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->token)) {
                $ticket->token = $ticket->attendee?->token ?? \App\Services\TicketToken::generate();
            }
        });
    }
}
