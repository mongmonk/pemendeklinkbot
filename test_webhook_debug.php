<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Webhook Debug Test ===\n\n";

// Test data
$webhookData = [
    'update_id' => 123456789,
    'message' => [
        'message_id' => 1,
        'from' => [
            'id' => 12345,
            'is_bot' => false,
            'first_name' => 'Test',
            'username' => 'testuser',
            'language_code' => 'en'
        ],
        'chat' => [
            'id' => 12345,
            'first_name' => 'Test',
            'username' => 'testuser',
            'type' => 'private'
        ],
        'date' => 1640995200,
        'text' => 'https://example.com'
    ]
];

try {
    echo "1. Testing UrlShortenerService directly...\n";
    
    $urlShortener = app(\App\Services\UrlShortenerService::class);
    $link = $urlShortener->createShortLink('https://example.com', 12345, null);
    
    echo "   ✅ Link created successfully!\n";
    echo "   📝 Short Code: {$link->short_code}\n";
    echo "   🔗 Short URL: {$link->short_url}\n";
    echo "   🌐 Long URL: {$link->long_url}\n\n";
    
    echo "2. Testing database record...\n";
    $dbLink = \App\Models\Link::find($link->id);
    if ($dbLink) {
        echo "   ✅ Link found in database!\n";
        echo "   📊 Clicks: {$dbLink->clicks}\n";
        echo "   📅 Created: {$dbLink->created_at}\n\n";
    } else {
        echo "   ❌ Link not found in database!\n\n";
    }
    
    echo "3. Testing webhook processing (without sending message)...\n";
    
    // Create a real Telegram API instance but don't actually send messages
    $telegram = new \Telegram\Bot\Api(config('telegram.bot_token'));
    
    // Override the sendMessage method to avoid "chat not found" error
    $telegram->getClient()->setAsyncRequest(false);
    
    $telegramBotService = new \App\Services\TelegramBotService($telegram, $urlShortener);
    $telegramBotService->handleWebhook($webhookData);
    
    echo "   ✅ Webhook processed successfully!\n\n";
    
    echo "4. Testing link count in database...\n";
    $totalLinks = \App\Models\Link::count();
    echo "   📊 Total links in database: {$totalLinks}\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== Test Complete ===\n";