<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'content',
        'variables',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the SMS logs for this template.
     */
    public function logs()
    {
        return $this->hasMany(SmsLog::class, 'template_id');
    }

    /**
     * Get all active templates of a specific type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveByType(string $type)
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->get();
    }
} 