<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Direct Click Test ===\n\n";

try {
    echo "1. Testing direct ClickLog creation...\n";
    
    // Prepare click data
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
    
    // Create click log directly
    $clickLog = \App\Models\ClickLog::create($clickData);
    
    echo "   ✅ ClickLog created with ID: {$clickLog->id}\n";
    
    // Verify in database
    $dbClick = \App\Models\ClickLog::find($clickLog->id);
    if ($dbClick) {
        echo "   ✅ ClickLog found in database with short_code: {$dbClick->short_code}\n";
    } else {
        echo "   ❌ ClickLog NOT found in database!\n";
    }
    
    echo "\n2. Testing analytics data...\n";
    
    // Test analytics
    $link = \App\Models\Link::where('short_code', '0YU7h')->first();
    if ($link) {
        $analytics = $link->getAnalyticsData();
        echo "   📊 Link analytics: {$analytics['total_clicks']} clicks, {$analytics['unique_clicks']} unique\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}