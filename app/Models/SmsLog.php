<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class SmsLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'reference',
        'recipient',
        'sender_id',
        'message',
        'status',
        'provider_status',
        'provider_message',
        'delivered_at',
        'last_status_check',
        'status_check_count',
        'error',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'delivered_at' => 'datetime',
        'last_status_check' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Scope a query to only include logs for a specific event.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $eventId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->whereJsonContains('metadata->event_id', $eventId);
    }

    /**
     * Scope a query to only include logs of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include logs with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include logs from a specific provider.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Get the event associated with the SMS log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'metadata->event_id');
    }

    /**
     * Get the ticket associated with the SMS log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'metadata->ticket_id');
    }

    /**
     * Get the attendee associated with the SMS log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendee()
    {
        return $this->belongsTo(Attendee::class, 'metadata->attendee_id');
    }

    /**
     * Scope a query to only include messages that need status check
     */
    public function scopeNeedsStatusCheck($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'sent')
              ->orWhere('status', 'unknown')
              ->orWhereNull('last_status_check')
              ->orWhere('last_status_check', '<', now()->subMinutes(5));
        })->where('status_check_count', '<', 10); // Limit retries
    }

    /**
     * Update the delivery status of the message
     */
    public function updateDeliveryStatus(array $statusData): bool
    {
        $this->status = $statusData['status'];
        $this->provider_status = $statusData['provider_status'];
        $this->provider_message = $statusData['provider_message'];
        $this->delivered_at = $statusData['delivered_at'] ? Carbon::parse($statusData['delivered_at']) : null;
        $this->last_status_check = now();
        $this->status_check_count = $this->status_check_count + 1;
        
        if ($statusData['error']) {
            $this->error = $statusData['error'];
        }

        return $this->save();
    }

    /**
     * Get the status badge class based on current status
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'delivered' => 'success',
            'sent' => 'info',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get a human-readable status message
     */
    public function getStatusMessage(): string
    {
        if ($this->error) {
            return "Failed: {$this->error}";
        }

        return match($this->status) {
            'delivered' => 'Delivered',
            'sent' => 'Sent',
            'failed' => 'Failed',
            default => 'Unknown'
        };
    }
} 