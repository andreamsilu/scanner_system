<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'type',
        'name',
        'content',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

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