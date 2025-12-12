<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk Telegram Bot API
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', '8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8'),
    
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', null),
    
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', null),
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Konfigurasi rate limiting untuk mencegah spam
    |
    */
    'rate_limit' => [
        'enabled' => env('TELEGRAM_RATE_LIMIT_ENABLED', true),
        'attempts' => env('TELEGRAM_RATE_LIMIT_ATTEMPTS', 5),
        'minutes' => env('TELEGRAM_RATE_LIMIT_MINUTES', 1),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Pengaturan keamanan untuk bot
    |
    */
    'security' => [
        'allowed_updates' => ['message'],
        'validate_webhook' => env('TELEGRAM_VALIDATE_WEBHOOK', true),
    ],
];