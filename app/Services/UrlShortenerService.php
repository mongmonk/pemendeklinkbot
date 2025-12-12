<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UrlShortenerService
{
    /**
     * Generate a unique short code
     */
    public function generateShortCode(int $length = 5): string
    {
        do {
            $shortCode = Str::random($length);
        } while ($this->shortCodeExists($shortCode));

        return $shortCode;
    }

    /**
     * Check if short code already exists
     */
    public function shortCodeExists(string $shortCode): bool
    {
        return Link::where('short_code', $shortCode)->exists();
    }

    /**
     * Create a new short link
     */
    public function createShortLink(string $longUrl, ?int $telegramUserId = null, ?string $customCode = null): Link
    {
        // Validate URL
        if (!$this->isValidUrl($longUrl)) {
            throw new \InvalidArgumentException('URL tidak valid');
        }

        // Use custom code if provided, otherwise generate random
        $shortCode = $customCode ?: $this->generateShortCode();

        // Validate custom code if provided
        if ($customCode) {
            if (!$this->isValidCustomCode($customCode)) {
                throw new \InvalidArgumentException('Custom alias tidak valid. Hanya huruf, angka, hyphen, dan underscore yang diperbolehkan (maksimal 15 karakter)');
            }
            
            // Check if custom code is already taken
            if ($this->shortCodeExists($customCode)) {
                throw new \InvalidArgumentException('Kode short sudah digunakan');
            }
        }

        // Create the link
        $link = Link::create([
            'short_code' => $shortCode,
            'long_url' => $longUrl,
            'is_custom' => !is_null($customCode),
            'telegram_user_id' => $telegramUserId,
        ]);

        // Cache the link for faster redirects
        Cache::put("short_url:{$shortCode}", $longUrl, now()->addDays(30));

        return $link;
    }

    /**
     * Get long URL by short code
     */
    public function getLongUrl(string $shortCode): ?string
    {
        // Try to get from cache first
        $cacheKey = "short_url:{$shortCode}";
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData !== null) {
            // If cached data is false, it means the link doesn't exist or is disabled
            if ($cachedData === false) {
                return null;
            }
            return $cachedData;
        }

        // Get from database if not in cache
        $link = Link::where('short_code', $shortCode)->active()->first();
        
        if ($link) {
            // Cache the result for 30 days
            Cache::put($cacheKey, $link->long_url, now()->addDays(30));
            return $link->long_url;
        }

        // Cache negative result for 5 minutes to prevent repeated database queries
        Cache::put($cacheKey, false, now()->addMinutes(5));
        return null;
    }

    /**
     * Get link by short code with full model
     */
    public function getLinkByShortCode(string $shortCode): ?Link
    {
        return Link::where('short_code', $shortCode)->active()->first();
    }

    /**
     * Get links by Telegram user ID
     */
    public function getLinksByUser(int $telegramUserId, int $limit = 10)
    {
        return Link::byUser($telegramUserId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular links
     */
    public function getPopularLinks(int $limit = 10)
    {
        return Link::active()->popular($limit)
            ->get();
    }

    /**
     * Get custom links
     */
    public function getCustomLinks(int $limit = 10)
    {
        return Link::custom()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get random links
     */
    public function getRandomLinks(int $limit = 10)
    {
        return Link::random()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get analytics data
     */
    public function getAnalyticsData(string $shortCode): array
    {
        $link = $this->getLinkByShortCode($shortCode);
        
        if (!$link) {
            return [];
        }

        $clickLogs = $link->clickLogs();

        return [
            'total_clicks' => $link->clicks,
            'unique_clicks' => $link->unique_clicks,
            'today_clicks' => $link->today_clicks,
            'countries' => $clickLogs->select('country', DB::raw('count(*) as count'))
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->get(),
            'devices' => $clickLogs->select('device_type', DB::raw('count(*) as count'))
                ->whereNotNull('device_type')
                ->groupBy('device_type')
                ->orderBy('count', 'desc')
                ->get(),
            'browsers' => $clickLogs->select('browser', DB::raw('count(*) as count'))
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->orderBy('count', 'desc')
                ->get(),
            'daily_clicks' => $clickLogs->select(
                    DB::raw('DATE(timestamp) as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy(DB::raw('DATE(timestamp)'))
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),
        ];
    }

    /**
     * Validate URL format
     */
    private function isValidUrl(string $url): bool
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Additional validation for security
        $parsedUrl = parse_url($url);
        
        // Check if scheme is http or https
        if (!in_array($parsedUrl['scheme'] ?? '', ['http', 'https'])) {
            return false;
        }

        // Check for localhost or private IPs
        $host = $parsedUrl['host'] ?? '';
        if (in_array($host, ['localhost', '127.0.0.1']) || 
            str_starts_with($host, '192.168.') || 
            str_starts_with($host, '10.') ||
            str_starts_with($host, '172.')) {
            return false;
        }

        return true;
    }

    /**
     * Validate custom alias format
     */
    public function isValidCustomCode(string $code): bool
    {
        // Check length (max 15 characters)
        if (strlen($code) > 15 || strlen($code) < 1) {
            return false;
        }

        // Check if only contains alphanumeric, hyphen, and underscore
        return preg_match('/^[a-zA-Z0-9_-]+$/', $code) === 1;
    }

    /**
     * Delete a link
     */
    public function deleteLink(string $shortCode, ?int $telegramUserId = null): bool
    {
        $query = Link::where('short_code', $shortCode);
        
        // If telegram user ID is provided, only allow deletion of own links
        if ($telegramUserId) {
            $query->where('telegram_user_id', $telegramUserId);
        }

        $link = $query->first();
        
        if ($link) {
            // Remove from cache
            Cache::forget("short_url:{$shortCode}");
            
            // Delete the link (cascade will delete click logs)
            return $link->delete();
        }

        return false;
    }

    /**
     * Disable a link
     */
    public function disableLink(string $shortCode, string $reason = null, ?int $telegramUserId = null): bool
    {
        $query = Link::where('short_code', $shortCode);
        
        // If telegram user ID is provided, only allow disabling of own links
        if ($telegramUserId) {
            $query->where('telegram_user_id', $telegramUserId);
        }

        $link = $query->first();
        
        if ($link) {
            // Remove from cache
            Cache::forget("short_url:{$shortCode}");
            
            // Disable the link
            return $link->disable($reason);
        }

        return false;
    }

    /**
     * Enable a link
     */
    public function enableLink(string $shortCode, ?int $telegramUserId = null): bool
    {
        $query = Link::where('short_code', $shortCode);
        
        // If telegram user ID is provided, only allow enabling of own links
        if ($telegramUserId) {
            $query->where('telegram_user_id', $telegramUserId);
        }

        $link = $query->first();
        
        if ($link) {
            // Cache the enabled link
            Cache::put("short_url:{$shortCode}", $link->long_url, now()->addDays(30));
            
            // Enable the link
            return $link->enable();
        }

        return false;
    }

    /**
     * Clear cache for a specific short code
     */
    public function clearCache(string $shortCode): void
    {
        Cache::forget("short_url:{$shortCode}");
    }

    /**
     * Warm up cache for popular links
     */
    public function warmUpCache(int $limit = 100): void
    {
        $popularLinks = Link::active()->popular($limit)->get();
        
        foreach ($popularLinks as $link) {
            Cache::put("short_url:{$link->short_code}", $link->long_url, now()->addDays(30));
        }
    }
}