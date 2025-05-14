<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'is_active',
        'last_active_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_active_at' => 'datetime'
    ];

    /**
     * Get the user that owns the scanner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the events that the scanner is assigned to.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_scanner')
            ->withTimestamps()
            ->withPivot(['assigned_at', 'assigned_by']);
    }

    /**
     * Get the scan logs for the scanner.
     */
    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Check if the scanner is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->last_active_at?->isAfter(now()->subMinutes(5));
    }

    /**
     * Create a scanner from a user.
     */
    public static function createFromUser(User $user): self
    {
        return static::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => true
        ]);
    }
}
