<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::updated(function ($user) {
            // Check if the user was just assigned the scanner role
            if ($user->isScanner() && !$user->scanner) {
                Scanner::createFromUser($user);
            }
        });
    }

    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->count() === count($roles);
    }

    /**
     * Get the events that the user is assigned to scan.
     */
    public function assignedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_scanner', 'scanner_id', 'event_id')
            ->withTimestamps();
    }

    /**
     * Get the scan logs created by this user.
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class, 'scanner_id');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a scanner.
     */
    public function isScanner(): bool
    {
        return $this->hasRole('scanner');
    }

    /**
     * Get the scans performed by this user.
     */
    public function scans()
    {
        return $this->hasMany(Scan::class, 'scanner_id');
    }

    /**
     * Get all events assigned to this scanner
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_scanner', 'scanner_id', 'event_id')
            ->withTimestamps()
            ->withPivot(['assigned_at', 'assigned_by'])
            ->with(['attendees' => function($query) {
                $query->select('id', 'name', 'email', 'event_id', 'token')
                    ->with(['ticket' => function($q) {
                        $q->select('id', 'attendee_id', 'status', 'used_at');
                    }]);
            }]);
    }

    /**
     * Get active events assigned to this scanner
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activeEvents(): BelongsToMany
    {
        return $this->events()
            ->where('status', 'active')
            ->where(function($query) {
                $query->where('start_date', '>=', now())
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Get past events assigned to this scanner
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pastEvents(): BelongsToMany
    {
        return $this->events()
            ->where('end_date', '<', now())
            ->orWhere('status', 'completed');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $role): void
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id');
        if ($roleId) {
            $this->roles()->syncWithoutDetaching([$roleId]);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $role): void
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id');
        if ($roleId) {
            $this->roles()->detach($roleId);
        }
    }

    /**
     * Sync the user's roles.
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('name', $roles)
            ->pluck('id')
            ->toArray();
        $this->roles()->sync($roleIds);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
