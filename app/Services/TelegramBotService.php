<?php

namespace App\Services;

use App\Models\Link;
use App\Services\UrlShortenerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class TelegramBotService
{
    protected Api $telegram;
    protected UrlShortenerService $urlShortener;

    public function __construct(Api $telegram, UrlShortenerService $urlShortener)
    {
        $this->telegram = $telegram;
        $this->urlShortener = $urlShortener;
    }

    /**
     * Handle incoming webhook update
     */
    public function handleWebhook($update): void
    {
        try {
            // Handle both Update object and array
            if ($update instanceof Update) {
                $message = $update->getMessage();
            } elseif (is_array($update)) {
                $message = $update['message'] ?? null;
            } else {
                return;
            }
            
            if (!$message) {
                return;
            }

            // Handle both Update object and array for message
            if ($message instanceof \Telegram\Bot\Objects\Message) {
                $chatId = $message->getChat()->getId();
                $text = $message->getText();
                $userId = $message->getFrom()->getId();
            } elseif (is_array($message)) {
                $chatId = $message['chat']['id'] ?? null;
                $text = $message['text'] ?? null;
                $userId = $message['from']['id'] ?? null;
            } else {
                return;
            }

            // Handle commands
            if ($text && str_starts_with($text, '/')) {
                $this->handleCommand($chatId, $text, $userId);
            } elseif ($text) {
                // Handle URL input
                $this->handleUrlInput($chatId, $text, $userId);
            }
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            
            if (isset($chatId)) {
                $this->sendMessage($chatId, '❌ Terjadi kesalahan. Silakan coba lagi.');
            }
        }
    }

    /**
     * Handle bot commands
     */
    private function handleCommand(int $chatId, string $command, int $userId): void
    {
        // Apply rate limiting
        if (!$this->checkRateLimit($userId)) {
            $this->sendMessage($chatId, '⚠️ Terlalu banyak permintaan! Silakan tunggu sebentar sebelum mencoba lagi.');
            return;
        }

        $command = strtolower(trim($command));
        $parts = explode(' ', $command, 2);
        $baseCommand = $parts[0];
        $parameter = isset($parts[1]) ? trim($parts[1]) : null;

        switch ($baseCommand) {
            case '/start':
                $this->sendWelcomeMessage($chatId);
                break;
                
            case '/help':
                $this->sendHelpMessage($chatId);
                break;
                
            case '/short':
                $this->handleShortCommand($chatId, $parameter, $userId);
                break;
                
            case '/stats':
                $this->handleStatsCommand($chatId, $parameter, $userId);
                break;
                
            case '/mylinks':
                $this->sendUserLinks($chatId, $userId);
                break;
                
            case '/popular':
                $this->sendPopularLinks($chatId);
                break;
                
            default:
                $this->sendMessage($chatId, '❌ Perintah tidak dikenal. Ketik /help untuk bantuan.');
        }
    }

    /**
     * Handle URL input
     */
    private function handleUrlInput(int $chatId, string $text, int $userId): void
    {
        // Apply rate limiting
        if (!$this->checkRateLimit($userId)) {
            $this->sendMessage($chatId, '⚠️ Terlalu banyak permintaan! Silakan tunggu sebentar sebelum mencoba lagi.');
            return;
        }

        try {
            // Parse input to check if it contains custom alias
            $parts = explode(' ', $text, 2);
            $url = $parts[0];
            $customAlias = isset($parts[1]) ? trim($parts[1]) : null;
            
            // Check if input is a valid URL
            if (!$this->isValidUrl($url)) {
                $this->sendMessage($chatId, '❌ URL tidak valid. Silakan masukkan URL yang benar.\n\nFormat: `https://example.com [alias]`');
                return;
            }
            
            // Validate custom alias if provided
            if ($customAlias && !$this->urlShortener->isValidCustomCode($customAlias)) {
                $this->sendMessage($chatId, '❌ Custom alias tidak valid. Hanya huruf, angka, hyphen, dan underscore yang diperbolehkan (maksimal 15 karakter).\n\nFormat: `https://example.com [alias]`');
                return;
            }
            
            // Create short link
            $link = $this->urlShortener->createShortLink($url, $userId, $customAlias);
            
            // Send success message
            $shortUrl = $link->short_url;
            $message = "✅ *Link berhasil dibuat!*\n\n";
            $message .= "🔗 Short URL: `{$shortUrl}`\n";
            $message .= "🌐 Original URL: {$url}\n";
            
            if ($customAlias) {
                $message .= "🏷️ Custom Alias: `{$customAlias}`\n";
            }
            
            $message .= "\nKlik untuk copy: `{$shortUrl}`";
            
            $this->sendMessage($chatId, $message, 'Markdown');
        } catch (\InvalidArgumentException $e) {
            $this->sendMessage($chatId, '❌ ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error creating short link: ' . $e->getMessage());
            $this->sendMessage($chatId, '❌ Gagal membuat link. Silakan coba lagi.');
        }
    }

    /**
     * Handle /short command
     */
    private function handleShortCommand(int $chatId, ?string $parameter, int $userId): void
    {
        if (!$parameter) {
            $this->sendMessage($chatId, '❌ Format perintah salah. Gunakan: `/short [URL]` atau `/short [URL] [custom_alias]`\n\nContoh: `/short https://example.com` atau `/short https://example.com mylink`');
            return;
        }

        // Parse parameter to extract URL and optional custom alias
        $parts = explode(' ', $parameter, 2);
        $url = $parts[0];
        $customAlias = isset($parts[1]) ? trim($parts[1]) : null;

        // Validate URL
        if (!$this->isValidUrl($url)) {
            $this->sendMessage($chatId, '❌ URL tidak valid. Pastikan URL dimulai dengan http:// atau https://\n\nContoh: `/short https://example.com`');
            return;
        }

        // Validate custom alias if provided
        if ($customAlias && !$this->urlShortener->isValidCustomCode($customAlias)) {
            $this->sendMessage($chatId, '❌ Custom alias tidak valid. Hanya huruf, angka, hyphen, dan underscore yang diperbolehkan (maksimal 15 karakter).\n\nContoh: `/short https://example.com my_link123`');
            return;
        }

        try {
            // Create short link
            $link = $this->urlShortener->createShortLink($url, $userId, $customAlias);
            
            // Send success message
            $shortUrl = $link->short_url;
            $message = "✅ *Link berhasil dibuat!*\n\n";
            $message .= "🔗 Short URL: `{$shortUrl}`\n";
            $message .= "🌐 Original URL: {$url}\n";
            
            if ($customAlias) {
                $message .= "🏷️ Custom Alias: `{$customAlias}`\n";
            }
            
            $message .= "\n💡 *Tips:* Anda dapat melihat statistik link dengan `/stats {$link->short_code}`";
            
            $this->sendMessage($chatId, $message, 'Markdown');
        } catch (\InvalidArgumentException $e) {
            $this->sendMessage($chatId, '❌ ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error creating short link: ' . $e->getMessage());
            $this->sendMessage($chatId, '❌ Gagal membuat link. Silakan coba lagi nanti.');
        }
    }

    /**
     * Handle /stats command
     */
    private function handleStatsCommand(int $chatId, ?string $parameter, int $userId): void
    {
        if ($parameter) {
            // Show stats for specific short code
            $this->sendLinkStats($chatId, $parameter);
        } else {
            // Show user's overall stats
            $this->sendUserStats($chatId, $userId);
        }
    }

    /**
     * Send statistics for a specific link
     */
    private function sendLinkStats(int $chatId, string $shortCode): void
    {
        try {
            $link = $this->urlShortener->getLinkByShortCode($shortCode);
            
            if (!$link) {
                $this->sendMessage($chatId, '❌ Link tidak ditemukan. Pastikan short code benar.');
                return;
            }

            $analytics = $this->urlShortener->getAnalyticsData($shortCode);
            
            $message = "📊 *Statistik Link: `{$shortCode}`*\n\n";
            $message .= "🔗 Short URL: {$link->short_url}\n";
            $message .= "🌐 Original URL: {$link->long_url}\n";
            $message .= "👁️ Total Klik: {$analytics['total_clicks']}\n";
            $message .= "👥 Klik Unik: {$analytics['unique_clicks']}\n";
            $message .= "📅 Klik Hari Ini: {$analytics['today_clicks']}\n";
            $message .= "📈 Status: {$link->status_text}\n";
            
            if ($link->is_custom) {
                $message .= "🏷️ Custom Alias: Ya\n";
            }
            
            if ($link->disabled && $link->disable_reason) {
                $message .= "🚫 Alasan Dinonaktifkan: {$link->disable_reason}\n";
            }
            
            $this->sendMessage($chatId, $message, 'Markdown');
        } catch (\Exception $e) {
            Log::error('Error getting link stats: ' . $e->getMessage());
            $this->sendMessage($chatId, '❌ Gagal mengambil statistik. Silakan coba lagi.');
        }
    }

    /**
     * Send welcome message
     */
    private function sendWelcomeMessage(int $chatId): void
    {
        $message = "👋 *Selamat datang di Aqwam URL Shortener Bot!*\n\n";
        $message .= "Saya dapat membantu Anda membuat link pendek dengan mudah.\n\n";
        $message .= "📝 *Cara penggunaan:*\n";
        $message .= "1. Kirim URL kepada saya\n";
        $message .= "2. Saya akan memberikan link pendek\n\n";
        $message .= "🏷️ *Custom Alias:*\n";
        $message .= "Format: `https://example.com [alias]`\n";
        $message .= "Contoh: `https://google.com search`\n\n";
        $message .= "🔧 *Perintah yang tersedia:*\n";
        $message .= "/help - Tampilkan bantuan\n";
        $message .= "/stats - Lihat statistik link Anda\n";
        $message .= "/mylinks - Lihat semua link Anda\n";
        $message .= "/popular - Lihat link populer\n\n";
        $message .= "Kirim URL sekarang untuk mulai! 🚀";
        
        $this->sendMessage($chatId, $message, 'Markdown');
    }

    /**
     * Send help message
     */
    private function sendHelpMessage(int $chatId): void
    {
        $message = "📖 *Bantuan Aqwam URL Shortener Bot*\n\n";
        $message .= "🔧 *Perintah:*\n";
        $message .= "• /start - Mulai bot\n";
        $message .= "• /help - Tampilkan bantuan ini\n";
        $message .= "• /stats - Lihat statistik link Anda\n";
        $message .= "• /mylinks - Lihat semua link Anda\n";
        $message .= "• /popular - Lihat link populer\n\n";
        $message .= "📝 *Cara membuat link pendek:*\n";
        $message .= "1. Kirim URL lengkap (contoh: https://example.com/very/long/url)\n";
        $message .= "2. Bot akan merespons dengan link pendek\n\n";
        $message .= "🏷️ *Custom Alias:*\n";
        $message .= "Format: `https://example.com [alias]`\n";
        $message .= "Contoh: `https://google.com search`\n";
        $message .= "Alias hanya boleh huruf, angka, hyphen, dan underscore (maks 15 karakter)\n\n";
        $message .= "🌐 *Domain:* aqwam.id\n";
        $message .= "📊 *Analytics:* Setiap link dilacak statistiknya\n\n";
        $message .= "Pertanyaan? Hubungi admin! 📞";
        
        $this->sendMessage($chatId, $message, 'Markdown');
    }

    /**
     * Send user statistics
     */
    private function sendUserStats(int $chatId, int $userId): void
    {
        $links = $this->urlShortener->getLinksByUser($userId, 5);
        
        if ($links->isEmpty()) {
            $this->sendMessage($chatId, '📊 Anda belum membuat link apapun. Kirim URL untuk mulai membuat link!');
            return;
        }

        $totalLinks = $links->count();
        $totalClicks = $links->sum('clicks');
        
        $message = "📊 *Statistik Link Anda*\n\n";
        $message .= "🔗 Total Link: {$totalLinks}\n";
        $message .= "👁️ Total Klik: {$totalClicks}\n\n";
        
        $message .= "📈 *5 Link Teratas:*\n";
        
        foreach ($links as $index => $link) {
            $shortCode = $link->short_code;
            $clicks = $link->clicks;
            $message .= ($index + 1) . ". `{$shortCode}` - {$clicks} klik\n";
        }
        
        $message .= "\n📋 Lihat semua link: /mylinks";
        
        $this->sendMessage($chatId, $message, 'Markdown');
    }

    /**
     * Send user links
     */
    private function sendUserLinks(int $chatId, int $userId): void
    {
        $links = $this->urlShortener->getLinksByUser($userId, 10);
        
        if ($links->isEmpty()) {
            $this->sendMessage($chatId, '📋 Anda belum membuat link apapun. Kirim URL untuk mulai membuat link!');
            return;
        }

        $message = "📋 *Link Anda:*\n\n";
        
        foreach ($links as $index => $link) {
            $shortCode = $link->short_code;
            $shortUrl = $link->short_url;
            $clicks = $link->clicks;
            $createdAt = $link->created_at->format('d/m/Y');
            
            $message .= ($index + 1) . ". `{$shortCode}` - {$clicks} klik\n";
            $message .= "   {$shortUrl}\n";
            $message .= "   📅 {$createdAt}\n\n";
        }
        
        $this->sendMessage($chatId, $message, 'Markdown');
    }

    /**
     * Send popular links
     */
    private function sendPopularLinks(int $chatId): void
    {
        $links = $this->urlShortener->getPopularLinks(5);
        
        if ($links->isEmpty()) {
            $this->sendMessage($chatId, '📊 Belum ada link yang populer saat ini.');
            return;
        }

        $message = "🔥 *Link Populer:*\n\n";
        
        foreach ($links as $index => $link) {
            $shortCode = $link->short_code;
            $shortUrl = $link->short_url;
            $clicks = $link->clicks;
            
            $message .= ($index + 1) . ". `{$shortCode}` - {$clicks} klik\n";
            $message .= "   {$shortUrl}\n\n";
        }
        
        $this->sendMessage($chatId, $message, 'Markdown');
    }

    /**
     * Send message to user
     */
    private function sendMessage(int $chatId, string $text, string $parseMode = null): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        
        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        
        $this->telegram->sendMessage($params);
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

        // Check if it starts with http:// or https://
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return false;
        }

        // Additional validation for security
        $parsedUrl = parse_url($url);
        
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
     * Check rate limit for user
     */
    private function checkRateLimit(int $userId): bool
    {
        $rateLimitConfig = config('telegram.rate_limit');
        
        if (!$rateLimitConfig['enabled']) {
            return true;
        }
        
        $cacheKey = "telegram_rate_limit:{$userId}";
        $attempts = Cache::get($cacheKey, 0);
        
        if ($attempts >= $rateLimitConfig['attempts']) {
            return false;
        }
        
        // Increment attempts counter
        Cache::put($cacheKey, $attempts + 1, now()->addMinutes($rateLimitConfig['minutes']));
        
        return true;
    }

    /**
     * Set webhook for the bot
     */
    public function setWebhook(string $url): bool
    {
        try {
            $result = $this->telegram->setWebhook(['url' => $url]);
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to set webhook: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        try {
            return $this->telegram->getWebhookInfo();
        } catch (\Exception $e) {
            Log::error('Failed to get webhook info: ' . $e->getMessage());
            return [];
        }
    }
}