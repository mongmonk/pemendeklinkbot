<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    $response = $app->handle(
        Illuminate\Http\Request::create('/nonexistent', 'GET')
    );
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
    if ($response->exception) {
        echo "Exception: " . $response->exception->getMessage() . "\n";
        echo "Exception Trace: " . $response->exception->getTraceAsString() . "\n";
    }
} catch (Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
    echo "Exception Trace: " . $e->getTraceAsString() . "\n";
}