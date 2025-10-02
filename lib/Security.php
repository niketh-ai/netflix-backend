<?php
class Security {
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function generateNonce($length = 16) {
        return bin2hex(random_bytes($length));
    }
    
    public static function validateOrigin() {
        $allowedOrigins = ['*']; // Allow all origins for mobile apps
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key");
    }
    
    public static function verifyAPIKey() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        if (empty($apiKey)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'API key required']);
            exit;
        }
        
        if (!hash_equals(API_KEY, $apiKey)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid API key']);
            exit;
        }
        
        return true;
    }
    
    public static function logSecurityEvent($event, $details = []) {
        // Ensure logs directory exists
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logFile = $logDir . '/security.log';
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
?>