<?php
class Validator {
    public function validateCredentials($username, $password) {
        // Username validation
        if (strlen($username) < 3 || strlen($username) > 20) {
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return false;
        }
        
        // Password validation
        if (strlen($password) < 4 || strlen($password) > 50) {
            return false;
        }
        
        return true;
    }
    
    public function validateTimestamp($timestamp) {
        $now = time();
        $requestTime = intval($timestamp);
        
        // Allow 5-minute window for clock skew
        return abs($now - $requestTime) <= 300;
    }
    
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>