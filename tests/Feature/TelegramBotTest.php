<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\TelegramBotService;
use App\Services\UrlShortenerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Telegram\Bot\Api;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected TelegramBotService $telegramBotService;
    protected UrlShortenerService $urlShortenerService;
    protected int $testUserId = 12345;
    protected int $testChatId = 67890;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->urlShortenerService = new UrlShortenerService();
        $telegramMock = $this->createMock(Api::class);
        $this->telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
    }

    /**
     * Test /start command
     */
    public function test_start_command(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/start',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        // Mock the sendMessage method to prevent actual API call
        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Selamat datang di Aqwam URL Shortener Bot');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /help command
     */
    public function test_help_command(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/help',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Bantuan Aqwam URL Shortener Bot');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /short command with valid URL
     */
    public function test_short_command_with_valid_url(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/short https://example.com',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link berhasil dibuat');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /short command with custom alias
     */
    public function test_short_command_with_custom_alias(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/short https://example.com myalias',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link berhasil dibuat');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /short command with invalid URL
     */
    public function test_short_command_with_invalid_url(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/short invalid-url',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'URL tidak valid');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /short command without URL
     */
    public function test_short_command_without_url(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/short',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Format perintah salah');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /short command with invalid custom alias
     */
    public function test_short_command_with_invalid_custom_alias(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/short https://example.com invalid@alias',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Custom alias tidak valid');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /stats command without parameter (user stats)
     */
    public function test_stats_command_without_parameter(): void
    {
        // Create a test link for the user
        $link = Link::factory()->create([
            'telegram_user_id' => $this->testUserId,
            'clicks' => 10
        ]);

        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/stats',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Statistik Link Anda');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /stats command with short code parameter
     */
    public function test_stats_command_with_short_code(): void
    {
        // Create a test link
        $link = Link::factory()->create([
            'short_code' => 'test123',
            'clicks' => 25
        ]);

        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/stats test123',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Statistik Link: `test123`');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /stats command with non-existent short code
     */
    public function test_stats_command_with_nonexistent_short_code(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/stats nonexistent',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link tidak ditemukan');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /mylinks command
     */
    public function test_mylinks_command(): void
    {
        // Create test links for the user
        Link::factory()->count(3)->create([
            'telegram_user_id' => $this->testUserId
        ]);

        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/mylinks',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link Anda:');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /mylinks command when user has no links
     */
    public function test_mylinks_command_when_user_has_no_links(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/mylinks',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'belum membuat link apapun');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test /popular command
     */
    public function test_popular_command(): void
    {
        // Create test links
        Link::factory()->count(3)->create([
            'clicks' => rand(10, 100)
        ]);

        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/popular',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link Populer:');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test direct URL input
     */
    public function test_direct_url_input(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => 'https://example.com',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link berhasil dibuat');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test direct URL input with custom alias
     */
    public function test_direct_url_input_with_custom_alias(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => 'https://example.com myalias',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Link berhasil dibuat');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }

    /**
     * Test rate limiting
     */
    public function test_rate_limiting(): void
    {
        // Clear any existing rate limit cache
        Cache::forget("telegram_rate_limit:{$this->testUserId}");

        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/help',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        // Test first request (should succeed)
        $telegramMock1 = $this->createMock(Api::class);
        $telegramMock1->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId &&
                       !str_contains($params['text'], 'Terlalu banyak permintaan');
            }));

        $telegramBotService1 = new TelegramBotService($telegramMock1, $this->urlShortenerService);
        $telegramBotService1->handleWebhook($update);

        // Test rate limit (simulate 5 rapid requests)
        $telegramMock2 = $this->createMock(Api::class);
        $telegramMock2->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId &&
                       str_contains($params['text'], 'Terlalu banyak permintaan');
            }));

        $telegramBotService2 = new TelegramBotService($telegramMock2, $this->urlShortenerService);
        
        // Simulate rate limit by directly setting cache
        Cache::put("telegram_rate_limit:{$this->testUserId}", 5, now()->addMinutes(1));
        
        $telegramBotService2->handleWebhook($update);
    }

    /**
     * Test unknown command
     */
    public function test_unknown_command(): void
    {
        $update = [
            'message' => [
                'chat' => ['id' => $this->testChatId],
                'text' => '/unknowncommand',
                'from' => ['id' => $this->testUserId]
            ]
        ];

        $telegramMock = $this->createMock(Api::class);
        $telegramMock->expects($this->once())
            ->method('sendMessage')
            ->with($this->callback(function ($params) {
                return $params['chat_id'] === $this->testChatId && 
                       str_contains($params['text'], 'Perintah tidak dikenal');
            }));

        $telegramBotService = new TelegramBotService($telegramMock, $this->urlShortenerService);
        $telegramBotService->handleWebhook($update);
    }
}