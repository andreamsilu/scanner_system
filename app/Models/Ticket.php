<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'event_id',
        'user_id',
        'status',
        'type',
        'last_scanned_at'
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function attendee()
    {
        return $this->hasOne(Attendee::class);
    }

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }

    public function last_scan()
    {
        return $this->hasOne(Scan::class)->latest();
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isUsed()
    {
        return $this->status === 'used';
    }

    public function isDeactivated()
    {
        return $this->status === 'deactivated';
    }

    public function activate()
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate()
    {
        $this->update(['status' => 'deactivated']);
    }

    public function markAsUsed()
    {
        $this->update(['status' => 'used']);
    }

    public function isAssigned()
    {
        return $this->attendee !== null;
    }

    public function assignToAttendee(Attendee $attendee)
    {
        if ($this->isAssigned()) {
            throw new \Exception('Ticket is already assigned');
        }

        if ($attendee->event_id !== $this->event_id) {
            throw new \Exception('Attendee does not belong to this event');
        }

        $attendee->assignTicket($this);
    }

    public function unassignFromAttendee()
    {
        if ($this->attendee) {
            $this->attendee->removeTicket();
        }
    }
} 