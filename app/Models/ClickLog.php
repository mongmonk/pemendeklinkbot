<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClickLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_code',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'device_type',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public $timestamps = false;

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class, 'short_code', 'short_code');
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope by device type
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope by browser
     */
    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);
    }

    /**
     * Get formatted timestamp
     */
    public function getFormattedTimestampAttribute(): string
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }

    /**
     * Get short user agent (truncated)
     */
    public function getShortUserAgentAttribute(): string
    {
        return strlen($this->user_agent) > 100
            ? substr($this->user_agent, 0, 100) . '...'
            : $this->user_agent;
    }

    /**
     * Get domain from referer
     */
    public function getRefererDomainAttribute(): ?string
    {
        if (!$this->referer) {
            return null;
        }

        $parsedUrl = parse_url($this->referer);
        return $parsedUrl['host'] ?? null;
    }
}
