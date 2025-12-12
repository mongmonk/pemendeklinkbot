<?php

namespace App\Http\Controllers;

use App\Models\ClickLog;
use App\Models\Link;
use App\Services\UrlShortenerService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Jenssegers\Agent\Agent;

class RedirectController extends Controller
{
    protected UrlShortenerService $urlShortener;
    protected ?Reader $geoIpReader = null;

    public function __construct(UrlShortenerService $urlShortener)
    {
        $this->urlShortener = $urlShortener;
        
        // Initialize GeoIP reader if database file exists
        $geoIpPath = storage_path('app/geoip/GeoLite2-City.mmdb');
        if (file_exists($geoIpPath)) {
            $this->geoIpReader = new Reader($geoIpPath);
        }
    }

    /**
     * Redirect from short code to original URL with optimizations
     */
    public function redirect(string $shortCode): RedirectResponse
    {
        $startTime = microtime(true);
        
        // Validate short code format
        if (!$this->isValidShortCode($shortCode)) {
            Log::warning('Invalid short code format: ' . $shortCode);
            abort(404, 'Link tidak ditemukan');
        }

        // Apply rate limiting
        $executed = RateLimiter::attempt(
            'redirect:' . request()->ip(),
            30, // 30 requests per minute
            function() {},
            60 // 60 seconds
        );

        if (!$executed) {
            Log::warning('Rate limit exceeded for IP: ' . request()->ip());
            abort(429, 'Terlalu banyak permintaan, coba lagi dalam beberapa saat');
        }

        // Check for redirect loops
        $referer = request()->header('referer');
        if ($referer && $this->isRedirectLoop($referer, $shortCode)) {
            Log::warning('Redirect loop detected for short code: ' . $shortCode);
            abort(419, 'Redirect loop detected');
        }

        try {
            // Try to get from cache first
            $cacheKey = "redirect:{$shortCode}";
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData && $cachedData !== false) {
                // Log cache hit for monitoring
                Log::info('Cache hit for short code: ' . $shortCode);
                
                // Log click asynchronously
                $this->logClickAsync($shortCode, $cachedData['link_id']);
                
                $responseTime = (microtime(true) - $startTime) * 1000;
                Log::info('Redirect completed from cache', [
                    'short_code' => $shortCode,
                    'response_time_ms' => round($responseTime, 2),
                    'cached' => true
                ]);
                
                return redirect($cachedData['long_url'], 301);
            }

            // Get the link from database
            $link = $this->urlShortener->getLinkByShortCode($shortCode);
            
            if (!$link) {
                // Cache negative result for 5 minutes
                Cache::put($cacheKey, false, now()->addMinutes(5));
                abort(404, 'Link tidak ditemukan');
            }

            // Check if link is disabled (if we add this feature later)
            if (isset($link->disabled) && $link->disabled) {
                abort(410, 'Link telah dinonaktifkan');
            }

            // Cache the link data for faster redirects
            Cache::put($cacheKey, [
                'long_url' => $link->long_url,
                'link_id' => $link->id
            ], now()->addDays(30));

            // Increment click count
            $link->incrementClicks();
            
            // Log click analytics asynchronously to avoid slowing down redirect
            $this->logClickAsync($shortCode, $link->id);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            Log::info('Redirect completed from database', [
                'short_code' => $shortCode,
                'response_time_ms' => round($responseTime, 2),
                'cached' => false
            ]);

            // Redirect to original URL with HTTP 301 (permanent redirect)
            return redirect($link->long_url, 301);
        } catch (\Exception $e) {
            Log::error('Redirect error: ' . $e->getMessage(), [
                'short_code' => $shortCode,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            // Re-throw the exception if it's not a NotFoundException
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                abort(404, 'Link tidak ditemukan');
            }
            
            abort(500, 'Terjadi kesalahan saat mengalihkan link');
        }
    }

    /**
     * Validate short code format
     */
    private function isValidShortCode(string $shortCode): bool
    {
        // Check length (1-15 characters)
        if (strlen($shortCode) < 1 || strlen($shortCode) > 15) {
            return false;
        }

        // Check if only contains alphanumeric, hyphen, and underscore
        return preg_match('/^[a-zA-Z0-9_-]+$/', $shortCode) === 1;
    }

    /**
     * Check for redirect loops
     */
    private function isRedirectLoop(string $referer, string $shortCode): bool
    {
        $currentDomain = parse_url(config('app.url'), PHP_URL_HOST);
        $refererDomain = parse_url($referer, PHP_URL_HOST);
        
        // If referer is from the same domain, check if it contains the same short code
        if ($refererDomain === $currentDomain) {
            return strpos($referer, $shortCode) !== false;
        }
        
        return false;
    }

    /**
     * Initialize GeoIP reader if not already initialized
     */
    private function initializeGeoIpReader(): void
    {
        if (!$this->geoIpReader) {
            $geoIpPath = storage_path('app/geoip/GeoLite2-City.mmdb');
            if (file_exists($geoIpPath)) {
                try {
                    $this->geoIpReader = new Reader($geoIpPath);
                } catch (\Exception $e) {
                    Log::error('Failed to initialize GeoIP reader: ' . $e->getMessage());
                    $this->geoIpReader = null;
                }
            } else {
                $this->geoIpReader = null;
            }
        }
    }

    /**
     * Log click asynchronously
     */
    private function logClickAsync(string $shortCode, int $linkId): void
    {
        try {
            $request = request();
            
            // Prepare click data
            $clickData = [
                'short_code' => $shortCode,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent(), 0, 500),
                'referer' => substr($request->header('referer'), 0, 500),
                'timestamp' => now(),
            ];

            // Get device and browser info
            $userAgent = $request->userAgent();
            try {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);
                $clickData['device_type'] = $agent->deviceType() ?: 'unknown';
                $clickData['browser'] = $agent->browser() ?: 'unknown';
                $clickData['browser_version'] = $agent->version($clickData['browser']) ?: 'unknown';
                $clickData['os'] = $agent->platform() ?: 'unknown';
                $clickData['os_version'] = $agent->version($clickData['os']) ?: 'unknown';
            } catch (\Exception $e) {
                // Set default values if agent detection fails
                $clickData['device_type'] = 'unknown';
                $clickData['browser'] = 'unknown';
                $clickData['browser_version'] = 'unknown';
                $clickData['os'] = 'unknown';
                $clickData['os_version'] = 'unknown';
                Log::error('Agent detection error: ' . $e->getMessage());
            }
            
            // Get location info
            try {
                if (!$this->geoIpReader) {
                    $this->initializeGeoIpReader();
                }
                
                if ($this->geoIpReader && $clickData['ip_address'] && $clickData['ip_address'] !== '127.0.0.1') {
                    $record = $this->geoIpReader->city($clickData['ip_address']);
                    $clickData['country'] = $record->country->isoCode;
                    $clickData['city'] = $record->city->name;
                }
            } catch (\Exception $e) {
                Log::error('GeoIP lookup error: ' . $e->getMessage());
            }

            // Queue the click logging to avoid slowing down the redirect
            dispatch(function() use ($clickData) {
                try {
                    $clickLog = ClickLog::create($clickData);
                    Log::info('Click logged successfully', [
                        'short_code' => $clickData['short_code'],
                        'click_id' => $clickLog->id,
                        'data_count' => count($clickData)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Async click logging error: ' . $e->getMessage(), [
                        'short_code' => $clickData['short_code'] ?? 'unknown',
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Click logging preparation error: ' . $e->getMessage());
            // Don't throw, continue with redirect
        }
    }

    /**
     * Log click analytics (legacy method for preview)
     */
    private function logClick(Link $link): void
    {
        try {
            $request = request();
            
            // Get IP address
            $ipAddress = $request->ip();
            
            // Get user agent
            $userAgent = $request->userAgent();
            
            // Get referer
            $referer = $request->header('referer');
            
            // Get device and browser info
            try {
                $agent = new Agent();
                $agent->setUserAgent($userAgent);
                $deviceType = $agent->deviceType() ?: 'unknown';
                $browser = $agent->browser() ?: 'unknown';
                $browserVersion = $agent->version($browser) ?: 'unknown';
            } catch (\Exception $e) {
                // Set default values if agent detection fails
                $deviceType = 'unknown';
                $browser = 'unknown';
                $browserVersion = 'unknown';
                Log::error('Agent detection error in logClick: ' . $e->getMessage());
            }
            $platform = $agent->platform() ?: 'unknown';
            $platformVersion = $agent->version($platform) ?: 'unknown';
            
            // Get location info
            $country = null;
            $city = null;
            
            if ($this->geoIpReader && $ipAddress && $ipAddress !== '127.0.0.1') {
                try {
                    $record = $this->geoIpReader->city($ipAddress);
                    $country = $record->country->isoCode;
                    $city = $record->city->name;
                } catch (\Exception $e) {
                    Log::error('GeoIP lookup error: ' . $e->getMessage());
                }
            }
            
            // Create click log using the link's logClick method
            $link->logClick([
                'ip_address' => $ipAddress,
                'user_agent' => substr($userAgent, 0, 500), // Limit to 500 chars
                'referer' => substr($referer, 0, 500), // Limit to 500 chars
                'country' => $country,
                'city' => $city,
                'device_type' => $deviceType,
                'browser' => $browser,
                'browser_version' => $browserVersion,
                'os' => $platform,
                'os_version' => $platformVersion,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Click logging error: ' . $e->getMessage());
            throw $e; // Re-throw to let the caller handle it
        }
    }

    /**
     * Show link preview page with detailed statistics
     */
    public function preview(string $shortCode)
    {
        // Validate short code format
        if (!$this->isValidShortCode($shortCode)) {
            abort(404, 'Link tidak ditemukan');
        }

        // Apply rate limiting for preview
        $executed = RateLimiter::attempt(
            'preview:' . request()->ip(),
            10, // 10 requests per minute
            function() {},
            60 // 60 seconds
        );

        if (!$executed) {
            abort(429, 'Terlalu banyak permintaan, coba lagi dalam beberapa saat');
        }

        $link = $this->urlShortener->getLinkByShortCode($shortCode);
        
        if (!$link) {
            abort(404, 'Link tidak ditemukan');
        }

        // Get detailed analytics data
        $analytics = $this->getDetailedAnalytics($shortCode);
        
        // Get recent clicks (last 50)
        $recentClicks = $this->getRecentClicks($shortCode, 50);
        
        return view('preview', [
            'link' => $link,
            'analytics' => $analytics,
            'recentClicks' => $recentClicks
        ]);
    }

    /**
     * Get detailed analytics data for a link
     */
    private function getDetailedAnalytics(string $shortCode): array
    {
        $link = $this->urlShortener->getLinkByShortCode($shortCode);
        
        if (!$link) {
            return [];
        }

        $clickLogs = $link->clickLogs();

        // Get basic stats
        $totalClicks = $link->clicks;
        $uniqueClicks = $link->unique_clicks;
        $todayClicks = $link->today_clicks;

        // Get countries data
        $countries = $clickLogs->select('country', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Get devices data
        $devices = $clickLogs->select('device_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->get();

        // Get browsers data
        $browsers = $clickLogs->select('browser', 'browser_version', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('browser')
            ->groupBy('browser', 'browser_version')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Get OS data
        $operatingSystems = $clickLogs->select('os', 'os_version', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('os')
            ->groupBy('os', 'os_version')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Get daily clicks for last 30 days
        $dailyClicks = $clickLogs->select(
                \Illuminate\Support\Facades\DB::raw('DATE(timestamp) as date'),
                \Illuminate\Support\Facades\DB::raw('count(*) as count')
            )
            ->where('timestamp', '>=', now()->subDays(30))
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(timestamp)'))
            ->orderBy('date', 'desc')
            ->get();

        // Get hourly distribution
        $hourlyClicks = $clickLogs->select(
                \Illuminate\Support\Facades\DB::raw('HOUR(timestamp) as hour'),
                \Illuminate\Support\Facades\DB::raw('count(*) as count')
            )
            ->where('timestamp', '>=', now()->subDays(7))
            ->groupBy(\Illuminate\Support\Facades\DB::raw('HOUR(timestamp)'))
            ->orderBy('hour')
            ->get();

        // Get referer domains
        $refererDomains = $clickLogs->select('referer', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->get()
            ->map(function ($item) {
                $parsedUrl = parse_url($item->referer);
                $domain = $parsedUrl['host'] ?? 'Direct';
                return (object)[
                    'domain' => $domain,
                    'count' => $item->count
                ];
            })
            ->groupBy('domain')
            ->map(function ($group) {
                return (object)[
                    'domain' => $group->first()->domain,
                    'count' => $group->sum('count')
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return [
            'total_clicks' => $totalClicks,
            'unique_clicks' => $uniqueClicks,
            'today_clicks' => $todayClicks,
            'countries' => $countries,
            'devices' => $devices,
            'browsers' => $browsers,
            'operating_systems' => $operatingSystems,
            'daily_clicks' => $dailyClicks,
            'hourly_clicks' => $hourlyClicks,
            'referer_domains' => $refererDomains,
            'click_rate_per_day' => $this->calculateClickRate($link),
        ];
    }

    /**
     * Get recent clicks for a link
     */
    private function getRecentClicks(string $shortCode, int $limit = 50): array
    {
        $clicks = ClickLog::where('short_code', $shortCode)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($click) {
                return [
                    'timestamp' => $click->timestamp->format('Y-m-d H:i:s'),
                    'ip_address' => $click->ip_address,
                    'country' => $click->country ?: 'Unknown',
                    'city' => $click->city ?: 'Unknown',
                    'device_type' => $click->device_type ?: 'Unknown',
                    'browser' => $click->browser ?: 'Unknown',
                    'os' => $click->os ?: 'Unknown',
                    'referer' => $click->referer_domain ?: 'Direct',
                ];
            })
            ->toArray();

        return $clicks;
    }

    /**
     * Calculate click rate per day
     */
    private function calculateClickRate(Link $link): float
    {
        $daysSinceCreation = $link->created_at->diffInDays(now()) ?: 1;
        return round($link->clicks / $daysSinceCreation, 2);
    }
}
