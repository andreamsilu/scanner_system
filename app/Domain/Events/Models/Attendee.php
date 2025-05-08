<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Attendee extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'company',
        'position',
        'registration_date',
        'status',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendee) {
            $attendee->qr_token = Str::random(32);
            $attendee->registration_date = now();
            $attendee->status = 'registered';
        });
    }
} 