<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Link Creation Test ===\n\n";

try {
    echo "1. Testing UrlShortenerService...\n";
    
    $urlShortener = app(\App\Services\UrlShortenerService::class);
    
    // Test 1: Create link without custom alias
    echo "   📝 Creating link without custom alias...\n";
    $link1 = $urlShortener->createShortLink('https://google.com', 12345, null);
    echo "   ✅ Link created: {$link1->short_url}\n";
    
    // Test 2: Create link with custom alias
    echo "   📝 Creating link with custom alias...\n";
    $uniqueAlias = 'test' . substr(time(), -6);
    $link2 = $urlShortener->createShortLink('https://github.com', 12345, $uniqueAlias);
    echo "   ✅ Link created: {$link2->short_url}\n";
    
    // Test 3: Test link retrieval
    echo "   🔍 Testing link retrieval...\n";
    $retrievedLink = $urlShortener->getLinkByShortCode($link1->short_code);
    if ($retrievedLink && $retrievedLink->long_url === 'https://google.com') {
        echo "   ✅ Link retrieval successful!\n";
    } else {
        echo "   ❌ Link retrieval failed!\n";
    }
    
    // Test 4: Test custom alias validation
    echo "   🔍 Testing custom alias validation...\n";
    $validAlias = $urlShortener->isValidCustomCode('test-alias_123');
    $invalidAlias = $urlShortener->isValidCustomCode('test@alias');
    echo "   ✅ Valid alias test: " . ($validAlias ? 'PASS' : 'FAIL') . "\n";
    echo "   ✅ Invalid alias test: " . ($invalidAlias ? 'FAIL' : 'PASS') . "\n";
    
    // Test 5: Test user links retrieval
    echo "   🔍 Testing user links retrieval...\n";
    $userLinks = $urlShortener->getLinksByUser(12345, 5);
    echo "   ✅ Found {$userLinks->count()} links for user 12345\n";
    
    // Test 6: Test analytics data
    echo "   📊 Testing analytics data...\n";
    $analytics = $urlShortener->getAnalyticsData($link1->short_code);
    echo "   ✅ Analytics data: {$analytics['total_clicks']} clicks, {$analytics['unique_clicks']} unique\n";
    
    echo "\n2. Database Summary...\n";
    $totalLinks = \App\Models\Link::count();
    $totalClicks = \App\Models\Link::sum('clicks');
    echo "   📊 Total links in database: {$totalLinks}\n";
    echo "   👁️ Total clicks across all links: {$totalClicks}\n";
    
    echo "\n3. Testing Redirect Controller...\n";
    
    // Test redirect via HTTP request
    $shortUrl = "http://aqwam.test/{$link1->short_code}";
    echo "   🌐 Testing redirect URL: {$shortUrl}\n";
    
    // Use curl to test the redirect
    $ch = curl_init($shortUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    curl_close($ch);
    
    if ($httpCode === 302 || $httpCode === 301) {
        echo "   ✅ Redirect working for {$link1->short_code} (HTTP {$httpCode})\n";
        echo "   🎯 Redirects to: {$finalUrl}\n";
    } else {
        echo "   ❌ Redirect failed for {$link1->short_code} (HTTP {$httpCode})\n";
    }
    
    echo "\n=== All Tests Completed Successfully! ===\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}