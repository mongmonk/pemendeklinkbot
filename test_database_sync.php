<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Database Synchronization Test ===\n\n";

try {
    echo "1. Testing Link vs ClickLog synchronization...\n";
    
    // Get link with short code
    $link = \App\Models\Link::where('short_code', '0YU7h')->first();
    if ($link) {
        echo "   📊 Link found: {$link->short_code} (ID: {$link->id})\n";
        echo "   👁️ Link clicks: {$link->clicks}\n";
        
        // Get actual click logs count
        $actualClickCount = \App\Models\ClickLog::where('short_code', '0YU7h')->count();
        echo "   📊 Actual click logs count: {$actualClickCount}\n";
        
        // Get click logs directly from relationship
        $relationshipClickCount = $link->clickLogs()->count();
        echo "   📊 Relationship click count: {$relationshipClickCount}\n";
        
        // Test increment
        echo "   🔄 Testing increment...\n";
        $link->incrementClicks();
        $link->refresh();
        
        // Check after increment
        $linkAfterIncrement = \App\Models\Link::find($link->id);
        echo "   📊 Link clicks after increment: {$linkAfterIncrement->clicks}\n";
        
        // Test direct click log creation
        echo "   📝 Creating direct click log...\n";
        $clickData = [
            'short_code' => '0YU7h',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'referer' => 'http://test.com',
            'country' => 'ID',
            'city' => 'Jakarta',
            'device_type' => 'desktop',
            'browser' => 'chrome',
            'browser_version' => '91.0',
            'os' => 'Windows',
            'os_version' => '10',
            'timestamp' => now(),
        ];
        
        $directClickLog = \App\Models\ClickLog::create($clickData);
        echo "   ✅ Direct click log created (ID: {$directClickLog->id})\n";
        
        // Verify counts again
        $finalActualClickCount = \App\Models\ClickLog::where('short_code', '0YU7h')->count();
        $finalRelationshipClickCount = $linkAfterIncrement->clickLogs()->count();
        
        echo "   📊 Final actual click logs count: {$finalActualClickCount}\n";
        echo "   📊 Final relationship click count: {$finalRelationshipClickCount}\n";
        echo "   📊 Link model clicks: {$linkAfterIncrement->clicks}\n";
        
        // Analysis
        if ($finalActualClickCount > $finalRelationshipClickCount) {
            echo "   ⚠️  WARNING: Actual count > Relationship count!\n";
        } elseif ($finalActualClickCount < $finalRelationshipClickCount) {
            echo "   ⚠️  WARNING: Actual count < Relationship count!\n";
        } else {
            echo "   ✅ Counts are synchronized\n";
        }
        
        // Test database transaction
        echo "\n2. Testing database transaction...\n";
        
        \Illuminate\Support\Facades\DB::transaction(function() {
            try {
                // Create test link
                $testLink = \App\Models\Link::create([
                    'short_code' => 'test-transaction',
                    'long_url' => 'https://example.com',
                    'telegram_user_id' => 99999,
                ]);
                
                echo "   ✅ Test link created (ID: {$testLink->id})\n";
                
                // Create test click log
                $testClickLog = \App\Models\ClickLog::create([
                    'short_code' => 'test-transaction',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Test Transaction',
                    'timestamp' => now(),
                ]);
                
                echo "   ✅ Test click log created (ID: {$testClickLog->id})\n";
                
                // Verify within transaction
                $countInTransaction = \App\Models\ClickLog::where('short_code', 'test-transaction')->count();
                echo "   📊 Click logs in transaction: {$countInTransaction}\n";
                
                // Rollback transaction
                throw new \Exception('Test rollback');
                
            } catch (\Exception $e) {
                echo "   ❌ Transaction error: " . $e->getMessage() . "\n";
            }
        });
        
        // Verify after rollback
        $countAfterRollback = \App\Models\ClickLog::where('short_code', 'test-transaction')->count();
        echo "   📊 Click logs after rollback: {$countAfterRollback}\n";
        
        if ($countAfterRollback === 0) {
            echo "   ✅ Transaction rollback successful\n";
        } else {
            echo "   ❌ Transaction rollback failed\n";
        }
        
    } else {
        echo "   ❌ Link not found\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}