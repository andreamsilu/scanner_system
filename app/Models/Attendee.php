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
        'qr_code'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendee) {
            // Generate unique 7-digit token if not provided
            if (empty($attendee->token)) {
                do {
                    // Generate a random 7-digit number
                    $token = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                } while (static::where('token', $token)->exists());
                
                $attendee->token = $token;
            }

            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($attendee->token);

            // Store QR code image
            $path = 'qr-codes/' . $attendee->token . '.png';
            Storage::disk('public')->put($path, $qrCode);

            $attendee->qr_code = $path;
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
     * Get the QR code URL
     */
    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code ? Storage::disk('public')->url($this->qr_code) : null;
    }

    /**
     * Get the validation URL for this attendee's token
     */
    public function getValidationUrlAttribute()
    {
        return route('attendees.validate', ['token' => $this->token]);
    }
} 