<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use App\Services\UrlShortenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class SetupTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-webhook {--url=} {--unset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup or unset Telegram bot webhook';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramBotService): int
    {
        $botToken = config('telegram.bot_token');
        
        if (!$botToken) {
            $this->error('Bot token tidak ditemukan. Pastikan TELEGRAM_BOT_TOKEN diatur di file .env');
            return Command::FAILURE;
        }

        $this->info('Bot Token: ' . substr($botToken, 0, 10) . '...');

        // Unset webhook if --unset option is provided
        if ($this->option('unset')) {
            $this->info('Menghapus webhook...');
            
            $telegram = new Api($botToken);
            $result = $telegram->removeWebhook();
            
            if ($result) {
                $this->info('✅ Webhook berhasil dihapus');
            } else {
                $this->error('❌ Gagal menghapus webhook');
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        }

        // Get webhook URL
        $webhookUrl = $this->option('url');
        
        if (!$webhookUrl) {
            $webhookUrl = config('app.url') . '/api/telegram/webhook';
            $this->info('Menggunakan URL webhook default: ' . $webhookUrl);
        } else {
            $this->info('Menggunakan URL webhook: ' . $webhookUrl);
        }

        // Validate URL
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $this->error('URL webhook tidak valid');
            return Command::FAILURE;
        }

        // Setup webhook
        $this->info('Mengatur webhook...');
        
        // Create a new API instance without loading config
        $telegram = new Api($botToken, false);
        
        // Set webhook without commands
        $params = [
            'url' => $webhookUrl,
        ];
        
        $webhookSecret = config('telegram.webhook_secret');
        if ($webhookSecret) {
            $params['secret_token'] = $webhookSecret;
        }
        
        $result = $telegram->setWebhook($params);
        $success = $result;
        
        if ($success) {
            $this->info('✅ Webhook berhasil diatur');
            
            // Get webhook info to verify
            $telegramInfo = new Api($botToken, false);
            $webhookInfo = $telegramInfo->getWebhookInfo();
            
            if (!empty($webhookInfo)) {
                $this->info('📊 Webhook Info:');
                $this->line('  URL: ' . ($webhookInfo['url'] ?? 'N/A'));
                $this->line('  Custom Certificate: ' . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
                $this->line('  Pending Updates: ' . ($webhookInfo['pending_update_count'] ?? 0));
                $this->line('  Last Error: ' . ($webhookInfo['last_error_message'] ?? 'None'));
            }
            
            return Command::SUCCESS;
        } else {
            $this->error('❌ Gagal mengatur webhook');
            return Command::FAILURE;
        }
    }
}