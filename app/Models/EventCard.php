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
        'used_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'used_at' => 'datetime'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now()
        ]);
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->is_used;
    }
} 