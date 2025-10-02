<?php
require_once 'middleware.php';

try {
    $middleware = new Middleware();
    $input = $middleware->validateRequest();
    
    // Apply rate limiting
    $deviceFingerprint = $input['device_fingerprint'] ?? '';
    $middleware->applyRateLimit($deviceFingerprint, 'config');
    
    // Get config from PostgreSQL
    $configData = dbGetConfig();
    if (!$configData) {
        throw new Exception('Configuration not available');
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'config' => $configData
        ],
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>