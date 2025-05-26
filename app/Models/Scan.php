<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'event_id',
        'scanner_id',
        'scanned_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function isDuplicate(): bool
    {
        return $this->status === 'duplicate';
    }

    public function markAsSuccessful(): void
    {
        $this->update(['status' => 'success']);
    }

    public function markAsInvalid(): void
    {
        $this->update(['status' => 'invalid']);
    }

    public function markAsDuplicate(): void
    {
        $this->update(['status' => 'duplicate']);
    }
} 