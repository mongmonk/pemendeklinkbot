<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimpleTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {--url=} {--unset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Telegram bot webhook using direct API call';

    /**
     * Execute the console command.
     */
    public function handle(): int
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
            
            $response = Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");
            
            if ($response->json()['ok']) {
                $this->info('✅ Webhook berhasil dihapus');
            } else {
                $this->error('❌ Gagal menghapus webhook: ' . ($response->json()['description'] ?? 'Unknown error'));
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
        
        $params = [
            'url' => $webhookUrl,
        ];
        
        $webhookSecret = config('telegram.webhook_secret');
        if ($webhookSecret) {
            $params['secret_token'] = $webhookSecret;
        }
        
        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", $params);
        $result = $response->json();
        
        if ($result['ok']) {
            $this->info('✅ Webhook berhasil diatur');
            
            // Get webhook info to verify
            $infoResponse = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $webhookInfo = $infoResponse->json();
            
            if ($webhookInfo['ok']) {
                $this->info('📊 Webhook Info:');
                $this->line('  URL: ' . ($webhookInfo['result']['url'] ?? 'N/A'));
                $this->line('  Custom Certificate: ' . ($webhookInfo['result']['has_custom_certificate'] ? 'Yes' : 'No'));
                $this->line('  Pending Updates: ' . ($webhookInfo['result']['pending_update_count'] ?? 0));
                $this->line('  Last Error: ' . ($webhookInfo['result']['last_error_message'] ?? 'None'));
            }
            
            return Command::SUCCESS;
        } else {
            $this->error('❌ Gagal mengatur webhook: ' . ($result['description'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}