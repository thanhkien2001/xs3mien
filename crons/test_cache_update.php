<?php
require_once __DIR__ . '/common.php';
require_once APP_PATH . '/config/services.php';

echo "🧪 TEST CACHE UPDATE MECHANISM\n";
echo "==============================\n\n";

// Test getBaseUrl function
echo "1. Testing getBaseUrl() function:\n";
$baseUrl = getBaseUrl();
echo "   Base URL: {$baseUrl}\n";
echo "   Environment: " . (php_sapi_name() === 'cli' ? 'CLI' : 'Web') . "\n";
echo "   Current directory: " . __DIR__ . "\n\n";

// Test curlGetContent function
echo "2. Testing curlGetContent() function:\n";
$testUrl = $baseUrl . '/';
echo "   Testing URL: {$testUrl}\n";

try {
    $response = curlGetContent($testUrl);
    $httpCode = $response['header']['http_code'];
    
    if ($httpCode == 200) {
        echo "   ✅ SUCCESS: HTTP {$httpCode}\n";
        echo "   Response length: " . strlen($response['body']) . " bytes\n";
    } else {
        echo "   ❌ ERROR: HTTP {$httpCode}\n";
        if (isset($response['error'])) {
            echo "   Error message: " . $response['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n3. Testing specific cache URLs:\n";
$testUrls = [
    $baseUrl . '/',
    $baseUrl . '/thongkemb/thongkexsmb',
    $baseUrl . '/ketquaxsmb/index'
];

foreach ($testUrls as $url) {
    echo "   Testing: {$url}\n";
    try {
        $response = curlGetContent($url);
        $httpCode = $response['header']['http_code'];
        
        if ($httpCode == 200) {
            echo "   ✅ SUCCESS: HTTP {$httpCode}\n";
        } else {
            echo "   ❌ ERROR: HTTP {$httpCode}\n";
            if (isset($response['error'])) {
                echo "   Error: " . $response['error'] . "\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "✅ Test completed!\n";
