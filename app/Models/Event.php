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
        'venue',
        'location',
        'start_date',
        'end_date',
        'capacity',
        'status',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'capacity' => 'integer'
    ];

    protected $appends = ['status_badge'];

    /**
     * Event status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all possible event statuses
     * 
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ONGOING => 'Ongoing',
            self::STATUS_FINISHED => 'Finished',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Determine the event status based on various conditions
     * 
     * @return string
     */
    public function determineStatus(): string
    {
        // If event is manually cancelled, keep that status
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        $now = now();
        
        // Check if event has started
        $hasStarted = $now->gte($this->start_date);
        
        // Check if event has ended
        $hasEnded = $now->gt($this->end_date);
        
        // Check if event is currently ongoing (within start and end time)
        $isOngoing = $hasStarted && !$hasEnded;
        
        // Check if event is about to start (within 24 hours)
        $isAboutToStart = $now->diffInHours($this->start_date) <= 24 && !$hasStarted;
        
        // Check if event has capacity
        $hasCapacity = $this->capacity > $this->attendees()->count();
        
        // Check if event has any attendees
        $hasAttendees = $this->attendees()->exists();

        // Determine status based on conditions
        if ($hasEnded) {
            return $hasAttendees ? self::STATUS_FINISHED : self::STATUS_CANCELLED;
        }
        
        if ($isOngoing) {
            return $hasAttendees ? self::STATUS_ONGOING : self::STATUS_ACTIVE;
        }
        
        if ($isAboutToStart) {
            return $hasAttendees ? self::STATUS_ACTIVE : self::STATUS_PENDING;
        }
        
        if (!$hasStarted) {
            return $hasAttendees ? self::STATUS_PENDING : self::STATUS_DRAFT;
        }

        return self::STATUS_DRAFT;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set the status based on conditions before saving
        static::saving(function ($event) {
            // Only update status if it's not manually set to cancelled
            if ($event->status !== self::STATUS_CANCELLED) {
                $event->status = $event->determineStatus();
            }
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

    /**
     * Get the status badge HTML with appropriate styling
     * 
     * @return string
     */
    public function getStatusBadgeAttribute(): string
    {
        $badgeClass = match($this->status) {
            self::STATUS_ACTIVE => 'bg-success',
            self::STATUS_ONGOING => 'bg-primary',
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_FINISHED => 'bg-info',
            self::STATUS_CANCELLED => 'bg-danger',
            default => 'bg-secondary'
        };

        $statusLabel = self::getStatuses()[$this->status] ?? ucfirst($this->status);
        
        return sprintf(
            '<span class="badge %s" title="%s">%s</span>',
            $badgeClass,
            $this->getStatusDescription(),
            $statusLabel
        );
    }

    /**
     * Get a human-readable description of the current status
     * 
     * @return string
     */
    public function getStatusDescription(): string
    {
        $now = now();
        $timeToStart = $now->diffForHumans($this->start_date, ['parts' => 2]);
        $timeToEnd = $now->diffForHumans($this->end_date, ['parts' => 2]);

        return match($this->status) {
            self::STATUS_DRAFT => 'Event is in draft mode. No attendees registered yet.',
            self::STATUS_PENDING => "Event starts {$timeToStart}. Has {$this->attendees()->count()} registered attendees.",
            self::STATUS_ACTIVE => "Event is active and starts {$timeToStart}. Ready for scanning.",
            self::STATUS_ONGOING => "Event is currently ongoing. Ends {$timeToEnd}.",
            self::STATUS_FINISHED => "Event ended {$timeToEnd}. Had {$this->attendees()->count()} attendees.",
            self::STATUS_CANCELLED => 'Event has been cancelled.',
            default => 'Unknown status'
        };
    }

    /**
     * Check if the event is currently active for scanning
     * 
     * @return bool
     */
    public function isActiveForScanning(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_ONGOING]) 
            && $this->hasAttendees()
            && !$this->isCancelled();
    }

    /**
     * Check if the event has any attendees
     * 
     * @return bool
     */
    public function hasAttendees(): bool
    {
        return $this->attendees()->exists();
    }

    /**
     * Check if the event is cancelled
     * 
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
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