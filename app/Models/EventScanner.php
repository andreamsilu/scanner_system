<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventScanner extends Pivot
{
    protected $casts = [
        'assigned_at' => 'datetime'
    ];
} 