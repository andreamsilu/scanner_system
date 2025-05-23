<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'venue',
        'location',
        'start_date',
        'end_date',
        'capacity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'capacity' => 'integer',
    ];

    protected $appends = ['status_badge'];

    /**
     * Determine the event status based on the event date
     * 
     * @return string
     */
    public function determineStatus(): string
    {
        $now = now();
        
        if ($this->start_date->isFuture()) {
            return 'pending';
        } elseif ($this->end_date->isPast()) {
            return 'finished';
        } else {
            return 'active';
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set the status based on dates before saving
        static::saving(function ($event) {
            $event->status = $event->determineStatus();
        });
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
        return match($this->status) {
            'active' => '<span class="badge bg-success">Active</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'finished' => '<span class="badge bg-info">Finished</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            default => '<span class="badge bg-secondary">Draft</span>'
        };
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getAttendeesWithTickets()
    {
        return $this->attendees()->with('ticket')->get();
    }

    public function getAvailableTickets()
    {
        return $this->tickets()->whereDoesntHave('attendee')->get();
    }

    public function assignTicketToAttendee(Attendee $attendee, Ticket $ticket)
    {
        if ($ticket->event_id !== $this->id) {
            throw new \Exception('Ticket does not belong to this event');
        }

        if ($ticket->attendee) {
            throw new \Exception('Ticket is already assigned');
        }

        $attendee->assignTicket($ticket);
    }

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }

    public function scanLogs(): HasManyThrough
    {
        return $this->hasManyThrough(ScanLog::class, Ticket::class, 'event_id', 'ticket_id');
    }

    /**
     * Get the scanners (users with scanner role) assigned to this event.
     */
    public function scanners()
    {
        return $this->belongsToMany(User::class, 'event_scanner', 'event_id', 'scanner_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'scanner');
            })
            ->withTimestamps()
            ->withPivot(['assigned_at', 'assigned_by']);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFinished()
    {
        return $this->status === 'finished';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getAvailableCapacity()
    {
        return $this->capacity - $this->attendees()->count();
    }

    public function hasAvailableCapacity()
    {
        return $this->getAvailableCapacity() > 0;
    }

    public function getRegistrationProgress()
    {
        return ($this->attendees()->count() / $this->capacity) * 100;
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