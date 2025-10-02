<?php
class RateLimiter {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function check($identifier, $action = 'default', $maxAttempts = null, $window = null) {
        $maxAttempts = $maxAttempts ?: API_RATE_LIMIT;
        $window = $window ?: API_RATE_WINDOW;
        
        // Clean old attempts
        $this->cleanOldAttempts($window);
        
        // Count recent attempts
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE identifier = ? AND action = ? AND attempt_time > NOW() - INTERVAL '? seconds'
        ");
        
        $stmt->execute([$identifier, $action, $window]);
        $result = $stmt->fetch();
        
        return $result['attempts'] < $maxAttempts;
    }
    
    public function increment($identifier, $action = 'default') {
        $stmt = $this->pdo->prepare("
            INSERT INTO login_attempts (identifier, action, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $identifier,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    }
    
    private function cleanOldAttempts($window) {
        $stmt = $this->pdo->prepare("
            DELETE FROM login_attempts 
            WHERE attempt_time < NOW() - INTERVAL '? seconds'
        ");
        $stmt->execute([$window]);
    }
}
?>