<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the admin that performed the activity.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the model that was acted upon.
     */
    public function subject()
    {
        return $this->morphTo('model');
    }

    /**
     * Log an activity
     */
    public static function log(array $data): self
    {
        return static::create([
            'admin_id' => auth('filament')->id(),
            'action' => $data['action'],
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'description' => $data['description'],
            'properties' => $data['properties'] ?? [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope by admin
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by model
     */
    public function scopeByModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Get formatted timestamp
     */
    public function getFormattedTimestampAttribute(): string
    {
        return $this->created_at->format('d M Y H:i:s');
    }

    /**
     * Get time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get action color
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'enabled' => 'success',
            'disabled' => 'danger',
            'login' => 'info',
            'logout' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil',
            'deleted' => 'heroicon-o-trash',
            'enabled' => 'heroicon-o-check-circle',
            'disabled' => 'heroicon-o-x-circle',
            'login' => 'heroicon-o-arrow-right-on-rectangle',
            'logout' => 'heroicon-o-arrow-left-on-rectangle',
            default => 'heroicon-o-information-circle',
        };
    }
}