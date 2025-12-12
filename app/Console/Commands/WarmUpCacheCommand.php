<?php

namespace App\Console\Commands;

use App\Services\UrlShortenerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmUpCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'url:warm-cache {--limit=100 : Number of popular links to cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up cache with popular links for better performance';

    /**
     * Execute the console command.
     */
    public function handle(UrlShortenerService $urlShortenerService): int
    {
        $limit = $this->option('limit');
        
        $this->info('Starting cache warm-up...');
        $this->info("Caching up to {$limit} popular links...");
        
        $startTime = microtime(true);
        
        try {
            $urlShortenerService->warmUpCache($limit);
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $this->info("Cache warm-up completed in {$executionTime}ms");
            $this->info('Popular links have been cached for faster redirects');
            
            Log::info('Cache warm-up completed', [
                'limit' => $limit,
                'execution_time_ms' => $executionTime
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Cache warm-up failed: ' . $e->getMessage());
            
            Log::error('Cache warm-up failed', [
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}