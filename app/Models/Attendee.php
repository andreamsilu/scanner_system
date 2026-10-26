<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'position',
        'token',
        'qr_code',
        'registration_status',
        'checked_in_at',
        'bulk_upload_id'
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'email' => 'string',
        'whatsapp_opted_in_at' => 'datetime',
    ];

    /**
     * The validation rules that apply to the model.
     *
     * @var array<string, string>
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'required|string|max:20',
        'position' => 'nullable|string|max:255',
        'event_id' => 'required|exists:events,id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendee) {
            // QR credentials are independent of the event's visible ticket number.
            if (empty($attendee->token)) {
                $attendee->token = \App\Services\TicketToken::generate();
            }

            $attendee->qr_code = $attendee->qr_code ?: app(\App\Services\QRCodeService::class)
                ->generateQRCode($attendee->token, $attendee->name, '');
        });

        static::deleting(function ($attendee) {
            // Delete QR code image when attendee is deleted
            if ($attendee->qr_code) {
                Storage::disk('public')->delete($attendee->qr_code);
            }
        });
    }

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
     * Get all tickets for this attendee.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
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

    /**
     * Get the check-in URL for this attendee
     */
    public function getCheckInUrlAttribute()
    {
        return route('scanner.check-in', ['token' => $this->token], true); // true to get absolute URL
    }

    /**
     * Get the QR code URL
     */
    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code ? '/storage/' . $this->qr_code : null;
    }

    /**
     * Get the validation URL for this attendee's token
     */
    public function getValidationUrlAttribute()
    {
        return route('attendees.validate', ['token' => $this->token]);
    }

    /**
     * Regenerate the QR code for this attendee
     */
    public function regenerateQrCode()
    {
        $previous = $this->qr_code;
        $path = app(\App\Services\QRCodeService::class)->generateForAttendee($this);
        $this->update(['qr_code' => $path]);
        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }
        return $this;
    }

    /**
     * Check if the attendee is registered.
     */
    public function isRegistered(): bool
    {
        return $this->registration_status === 'registered';
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
    public function markAsCheckedIn(): bool
    {
        return $this->update([
            'registration_status' => 'checked_in',
            'checked_in_at' => now()
        ]);
    }

    /**
     * Mark the attendee's registration as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'registration_status' => 'cancelled'
        ]);
    }

    /**
     * Reset the attendee's registration status to registered.
     */
    public function resetRegistrationStatus(): bool
    {
        return $this->update([
            'registration_status' => 'registered',
            'checked_in_at' => null
        ]);
    }

    /**
     * Assign a ticket to this attendee
     * 
     * @param \App\Models\Ticket $ticket
     * @throws \Exception
     */
    public function assignTicket(Ticket $ticket): void
    {
        if ($ticket->event_id !== $this->event_id) {
            throw new \Exception('Ticket does not belong to the same event as the attendee');
        }

        if ($ticket->attendee_id) {
            throw new \Exception('Ticket is already assigned to another attendee');
        }

        $ticket->update(['attendee_id' => $this->id]);
    }

    /**
     * Check if the attendee has a ticket
     * 
     * @return bool
     */
    public function hasTicket(): bool
    {
        return $this->ticket()->exists();
    }

    /**
     * Check if the attendee has checked in
     * 
     * @return bool
     */
    public function hasCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * Mark the attendee as checked in
     * 
     * @return bool
     */
    public function checkIn(): bool
    {
        return $this->update(['checked_in_at' => now()]);
    }

    /**
     * Get the QR code for this attendee
     * 
     * @return string|null
     */
    public function getQrCode(): ?string
    {
        if (!$this->qr_code) {
            $this->generateQrCode();
        }
        return $this->qr_code;
    }

    /**
     * Generate a QR code for this attendee
     * 
     * @return string
     */
    public function generateQrCode(): string
    {
        return $this->regenerateQrCode()->qr_code;
    }

}
