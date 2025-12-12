<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    protected TelegramBotService $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
    }

    /**
     * Handle Telegram webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Validate request
            $update = $request->all();
            
            if (empty($update)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request data'
                ], 400);
            }
            
            // Validate webhook secret if configured
            $webhookSecret = config('telegram.webhook_secret');
            if ($webhookSecret) {
                $signature = $request->header('X-Telegram-Bot-Api-Secret-Token');
                if ($signature !== $webhookSecret) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid webhook signature'
                    ], 401);
                }
            }
            
            // Create Telegram API instance
            $telegram = new Api(config('telegram.bot_token'));
            
            // Update the service with the telegram instance
            $this->telegramBotService = new TelegramBotService($telegram, app(\App\Services\UrlShortenerService::class));
            
            // Handle the update
            $this->telegramBotService->handleWebhook($update);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Set webhook
     */
    public function setWebhook(Request $request): JsonResponse
    {
        try {
            $url = $request->input('url');
            
            // Validate URL
            if (!$url) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'URL webhook diperlukan'
                ], 400);
            }
            
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format URL tidak valid'
                ], 400);
            }
            
            // Default webhook URL if not provided
            if (!$url) {
                $url = config('app.url') . '/api/telegram/webhook';
            }
            
            $telegram = new Api(config('telegram.bot_token'));
            $telegramBotService = new TelegramBotService($telegram, app(\App\Services\UrlShortenerService::class));
            
            $success = $telegramBotService->setWebhook($url);
            
            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook berhasil diatur',
                    'webhook_url' => $url
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengatur webhook'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Set webhook error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal'
            ], 500);
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): JsonResponse
    {
        try {
            $telegram = new Api(config('telegram.bot_token'));
            $telegramBotService = new TelegramBotService($telegram, app(\App\Services\UrlShortenerService::class));
            
            $info = $telegramBotService->getWebhookInfo();
            
            return response()->json([
                'status' => 'success',
                'data' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('Get webhook info error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan info webhook'
            ], 500);
        }
    }
}
