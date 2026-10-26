<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkUpload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'filename',
        'status',
        'total_records',
        'processed_records',
        'failed_records',
        'error_message',
        'error_log',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'failed_records' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the event that owns the bulk upload.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user that created the bulk upload.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attendees created from this bulk upload.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'bulk_upload_id');
    }
}
