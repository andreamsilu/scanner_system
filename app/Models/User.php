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
        'role',
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
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine if the user is a scanner.
     */
    public function isScanner(): bool
    {
        return $this->role === 'scanner';
    }

    /**
     * Get the scans performed by this user.
     */
    public function scans()
    {
        return $this->hasMany(Scan::class, 'scanner_id');
    }

    /**
     * Get the scan logs performed by this user.
     */
    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class, 'scanner_id');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $role): void
    {
        $this->update(['role' => $role]);
    }
}
