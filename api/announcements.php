<?php
require_once 'middleware.php';

try {
    $middleware = new Middleware();
    $input = $middleware->validateRequest();
    
    // Apply rate limiting
    $deviceFingerprint = $input['device_fingerprint'] ?? '';
    $middleware->applyRateLimit($deviceFingerprint, 'announcements');
    
    // Get announcements from PostgreSQL
    $announcementsData = dbGetAnnouncements();
    
    // Separate high and low priority
    $highPriority = [];
    $lowPriority = [];
    
    foreach ($announcementsData as $announcement) {
        if ($announcement['priority'] === 'high') {
            $highPriority[] = [
                'title' => $announcement['title'],
                'message' => $announcement['message'],
                'created_at' => $announcement['created_at']
            ];
        } else {
            $lowPriority[] = [
                'title' => $announcement['title'],
                'message' => $announcement['message'],
                'created_at' => $announcement['created_at']
            ];
        }
    }
    
    // Only return high priority announcements to mobile app
    $responseData = [
        'high_priority' => $highPriority,
        'low_priority' => [] // Don't send low priority to mobile
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $responseData,
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