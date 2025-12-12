<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_code',
        'long_url',
        'is_custom',
        'telegram_user_id',
        'clicks',
        'disabled',
        'disable_reason',
        'disabled_at',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'clicks' => 'integer',
        'disabled' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function clickLogs(): HasMany
    {
        return $this->hasMany(ClickLog::class, 'short_code', 'short_code');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'telegram_user_id', 'telegram_user_id');
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    public function getShortUrlAttribute(): string
    {
        $domain = app()->environment('production')
            ? config('domain.production')
            : config('domain.local');
        return $domain . '/' . $this->short_code;
    }

    /**
     * Log a click for this link
     */
    public function logClick(array $clickData): ClickLog
    {
        return $this->clickLogs()->create($clickData);
    }

    /**
     * Get unique click count
     */
    public function getUniqueClicksAttribute(): int
    {
        return $this->clickLogs()->distinct('ip_address')->count('ip_address');
    }

    /**
     * Get today's clicks
     */
    public function getTodayClicksAttribute(): int
    {
        return $this->clickLogs()
            ->whereDate('timestamp', today())
            ->count();
    }
    
    /**
     * Get analytics data (alias for getAnalyticsData)
     */
    public function getAnalyticsData(): array
    {
        $clickLogs = $this->clickLogs();

        return [
            'total_clicks' => $this->clicks,
            'unique_clicks' => $this->unique_clicks,
            'today_clicks' => $this->today_clicks,
        ];
    }

    /**
     * Scope for custom links
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    /**
     * Scope for random links
     */
    public function scopeRandom($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    /**
     * Scope popular links
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('clicks', 'desc')->limit($limit);
    }

    /**
     * Scope for active links only
     */
    public function scopeActive($query)
    {
        return $query->where('disabled', false);
    }

    /**
     * Scope for disabled links
     */
    public function scopeDisabled($query)
    {
        return $query->where('disabled', true);
    }

    /**
     * Disable a link
     */
    public function disable(string $reason = null): bool
    {
        $this->disabled = true;
        $this->disable_reason = $reason;
        $this->disabled_at = now();
        
        return $this->save();
    }

    /**
     * Enable a link
     */
    public function enable(): bool
    {
        $this->disabled = false;
        $this->disable_reason = null;
        $this->disabled_at = null;
        
        return $this->save();
    }

    /**
     * Check if link is disabled
     */
    public function isDisabled(): bool
    {
        return $this->disabled === true;
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        return $this->disabled ? 'Dinonaktifkan' : 'Aktif';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return $this->disabled ? 'danger' : 'success';
    }
}
