<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'content',
        'type',
        'is_active',
        'preview_image',
        'settings',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get template types
     */
    public static function getTypes(): array
    {
        return [
            'invitation' => 'Invitation Card',
            'ticket' => 'Event Ticket',
            'certificate' => 'Certificate',
            'badge' => 'Name Badge',
            'custom' => 'Custom Template'
        ];
    }

    /**
     * Render the template with given data
     */
    public function render(array $data = []): string
    {
        // Replace placeholders in the template content
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
            $content = str_replace("{{ " . $key . " }}", $value, $content);
        }
        
        return $content;
    }
}
