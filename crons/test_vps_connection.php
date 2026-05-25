<?php
require_once __DIR__ . '/common.php';

echo "🔍 TESTING VPS CONNECTION\n";
echo "========================\n\n";

// Test 1: Check if we can resolve the domain
echo "1. Testing domain resolution:\n";
$domain = 'soicau247.com';
$ip = gethostbyname($domain);
if ($ip === $domain) {
    echo "   ❌ Cannot resolve domain: {$domain}\n";
} else {
    echo "   ✅ Domain resolved to: {$ip}\n";
}

// Test 2: Check if we can connect to localhost
echo "\n2. Testing localhost connection:\n";
$localhostUrl = 'http://localhost';
try {
    $response = curlGetContent($localhostUrl);
    $httpCode = $response['header']['http_code'];
    echo "   ✅ Localhost connection: HTTP {$httpCode}\n";
} catch (Exception $e) {
    echo "   ❌ Localhost connection failed: " . $e->getMessage() . "\n";
}

// Test 3: Check if we can connect to the domain
echo "\n3. Testing domain connection:\n";
$domainUrl = 'https://soicau247.com';
try {
    $response = curlGetContent($domainUrl);
    $httpCode = $response['header']['http_code'];
    echo "   ✅ Domain connection: HTTP {$httpCode}\n";
} catch (Exception $e) {
    echo "   ❌ Domain connection failed: " . $e->getMessage() . "\n";
}

// Test 4: Check environment variables
echo "\n4. Environment check:\n";
echo "   API_URL: " . (getenv('API_URL') ?: 'Not set') . "\n";
echo "   Current working directory: " . getcwd() . "\n";
echo "   PHP SAPI: " . php_sapi_name() . "\n";

// Test 5: Test getBaseUrl function
echo "\n5. Testing getBaseUrl() function:\n";
$baseUrl = getBaseUrl();
echo "   Base URL returned: {$baseUrl}\n";

echo "\n✅ Test completed!\n";
