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
        'email' => 'string'
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
            // Generate unique 7-digit token if not provided
            if (empty($attendee->token)) {
                do {
                    // Generate a random 7-digit number
                    $token = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                } while (static::where('token', $token)->exists());
                
                $attendee->token = $token;
            }

            // Generate QR code with check-in URL (using absolute URL)
            $checkInUrl = route('scanner.check-in', ['token' => $attendee->token], true); // true to get absolute URL
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($checkInUrl);

            // Sanitize name for filename (remove special characters, replace spaces with hyphens)
            $sanitizedName = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($attendee->name));
            $sanitizedName = preg_replace('/-+/', '-', $sanitizedName); // Replace multiple hyphens with single hyphen
            $sanitizedName = trim($sanitizedName, '-'); // Remove leading/trailing hyphens

            // Store QR code image with attendee name in filename
            $path = 'qr-codes/' . $sanitizedName . '-' . $attendee->token . '.png';
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
     * Get the tickets for this attendee.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the active ticket for this attendee.
     */
    public function ticket()
    {
        return $this->tickets()->where('status', 'active')->first();
    }

    /**
     * Scope a query to only include active attendees (those with active tickets).
     */
    public function scopeActive($query)
    {
        return $query->whereHas('tickets', function ($query) {
            $query->where('status', 'active');
        });
    }

    /**
     * Check if the attendee has an active ticket.
     */
    public function hasActiveTicket(): bool
    {
        return $this->tickets()->where('status', 'active')->exists();
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
        // Delete old QR code if exists
        if ($this->qr_code) {
            Storage::disk('public')->delete($this->qr_code);
        }

        // Generate new QR code with check-in URL (using absolute URL)
        $checkInUrl = route('scanner.check-in', ['token' => $this->token], true);
        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($checkInUrl);

        // Sanitize name for filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($this->name));
        $sanitizedName = preg_replace('/-+/', '-', $sanitizedName);
        $sanitizedName = trim($sanitizedName, '-');

        // Store new QR code image with attendee name in filename
        $path = 'qr-codes/' . $sanitizedName . '-' . $this->token . '.png';
        Storage::disk('public')->put($path, $qrCode);

        // Update attendee record
        $this->qr_code = $path;
        $this->save();

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
} 