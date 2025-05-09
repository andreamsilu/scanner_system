<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'ticket_type',
        'status',
        'registration_date'
    ];

    protected $casts = [
        'registration_date' => 'datetime'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventCards(): HasMany
    {
        return $this->hasMany(EventCard::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }

    public function isCheckedIn(): bool
    {
        return $this->scanLogs()->where('status', 'success')->exists();
    }

    public function generateQrToken(): string
    {
        return bin2hex(random_bytes(16));
    }
} 