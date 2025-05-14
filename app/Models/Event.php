<?php

namespace App\Models;

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
        'location',
        'status',
        'capacity',
        'registration_deadline',
        'is_public',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'capacity' => 'integer'
    ];

    protected $appends = ['status_badge'];

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
        return match($this->status) {
            'active' => '<span class="badge bg-success">Active</span>',
            'completed' => '<span class="badge bg-info">Completed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            default => '<span class="badge bg-secondary">Draft</span>'
        };
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(EventCard::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Get the scanners assigned to this event.
     */
    public function scanners()
    {
        return $this->belongsToMany(Scanner::class, 'event_scanner')
            ->withTimestamps()
            ->withPivot(['assigned_at', 'assigned_by'])
            ->using(EventScanner::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUpcoming(): bool
    {
        return $this->start_date->isFuture();
    }

    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedDateRangeAttribute(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return 'No dates set';
        }

        $start = $this->start_date->format('M d, Y');
        $end = $this->end_date->format('M d, Y');

        if ($this->start_date->isSameDay($this->end_date)) {
            return $start;
        }

        return "$start - $end";
    }
} 