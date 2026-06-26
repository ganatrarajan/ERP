<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FcmService;

function assertTest($description, $condition) {
    if ($condition) {
        echo "✓ PASS: $description\n";
    } else {
        echo "✗ FAIL: $description\n";
        exit(1);
    }
}

echo "Testing FcmService with existing Service Account JSON...\n";

try {
    $fcmService = new FcmService();

    // Use Reflection to access the private getAccessToken method
    $reflection = new ReflectionClass(FcmService::class);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);

    // Clear fcm_access_token cache first to force regeneration
    cache()->forget('fcm_access_token');

    $accessToken = $method->invoke($fcmService);

    assertTest("Access Token is not empty", !empty($accessToken));
    assertTest("Access Token starts with google OAuth2 token prefix 'ya29.'", str_starts_with($accessToken, 'ya29.'));

    echo "\nOAuth2 access token successfully generated and cached: " . substr($accessToken, 0, 15) . "...\n";
    echo "FCM connection and credentials verified successfully!\n";
} catch (\Exception $e) {
    echo "✗ FAIL: Exception thrown during credentials verification: " . $e->getMessage() . "\n";
    exit(1);
}
