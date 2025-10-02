<?php
require_once '../config/database.php';
require_once '../lib/RateLimiter.php';
require_once '../lib/Validator.php';
require_once '../lib/Security.php';

class Middleware {
    private $rateLimiter;
    private $validator;
    
    public function __construct() {
        $this->rateLimiter = new RateLimiter();
        $this->validator = new Validator();
    }
    
    public function validateRequest() {
        // Set headers
        header('Content-Type: application/json');
        Security::validateOrigin();
        
        // Verify API key
        Security::verifyAPIKey();
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
        
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        // Get and validate input
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            exit;
        }
        
        return $this->validator->sanitizeInput($input);
    }
    
    public function applyRateLimit($identifier, $action = 'api_call') {
        if (!$this->rateLimiter->check($identifier, $action)) {
            http_response_code(429);
            echo json_encode([
                'success' => false, 
                'message' => 'Rate limit exceeded. Please try again later.'
            ]);
            exit;
        }
        
        $this->rateLimiter->increment($identifier, $action);
    }
    
    public function validateRequiredFields($input, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => "Missing required field: $field"
                ]);
                exit;
            }
        }
        return true;
    }
}
?>