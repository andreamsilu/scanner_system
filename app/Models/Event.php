<?php

namespace App\Models;

use App\Domain\Events\ValueObjects\EventDate;
use App\Domain\Events\ValueObjects\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'venue',
        'capacity',
        'status',
        'banner'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'capacity' => 'integer'
    ];

    public function getStartDateAttribute($value): EventDate
    {
        return new EventDate($this->asDateTime($value));
    }

    public function setStartDateAttribute($value): void
    {
        $this->attributes['start_date'] = $value instanceof EventDate ? $value->getValue() : $value;
    }

    public function getEndDateAttribute($value): EventDate
    {
        return new EventDate($this->asDateTime($value));
    }

    public function setEndDateAttribute($value): void
    {
        $this->attributes['end_date'] = $value instanceof EventDate ? $value->getValue() : $value;
    }

    public function getStatusAttribute($value): EventStatus
    {
        return EventStatus::fromString($value);
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = $value instanceof EventStatus ? $value->getValue() : $value;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner ? Storage::url($this->banner) : null;
    }

    public function getRemainingCapacityAttribute(): int
    {
        return $this->capacity - $this->attendees()->count();
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status->getBadgeClass();
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }
} 