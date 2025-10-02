<?php
require_once '../config/database.php';
require_once '../lib/JWT.php';
require_once '../lib/RateLimiter.php';
require_once '../lib/Validator.php';
require_once '../lib/Security.php';
require_once '../config/encryption.php';
require_once 'middleware.php';

// Initialize middleware
$middleware = new Middleware();

try {
    // Validate request
    $input = $middleware->validateRequest();
    
    // Apply rate limiting
    $deviceFingerprint = $input['device_fingerprint'] ?? '';
    $middleware->applyRateLimit($deviceFingerprint, 'login');
    
    // Validate required fields
    $middleware->validateRequiredFields($input, [
        'username', 'password', 'device_fingerprint', 'timestamp'
    ]);
    
    // Validate timestamp
    $validator = new Validator();
    if (!$validator->validateTimestamp($input['timestamp'])) {
        throw new Exception('Invalid timestamp');
    }
    
    // Decrypt credentials
    $username = Encryption::decrypt($input['username']);
    $password = Encryption::decrypt($input['password']);
    
    if (!$username || !$password) {
        throw new Exception('Invalid encrypted data');
    }
    
    // Validate credentials format
    if (!$validator->validateCredentials($username, $password)) {
        throw new Exception('Invalid credentials format');
    }
    
    // Get user from PostgreSQL
    $userData = dbGetUser($username);
    if (!$userData) {
        throw new Exception('User not found');
    }
    
    // Verify password
    if (!Encryption::verifyPassword($password, $userData['password_hash'])) {
        throw new Exception('Invalid password');
    }
    
    // Check if account is active
    if (isset($userData['is_active']) && !$userData['is_active']) {
        throw new Exception('Account suspended');
    }
    
    // Check expiry date
    if (isset($userData['expiry_date'])) {
        $expiry = new DateTime($userData['expiry_date']);
        if ($expiry < new DateTime()) {
            throw new Exception('Account expired');
        }
    }
    
    // Update last login
    dbUpdateUserDevices($username, $deviceFingerprint);
    
    // Generate JWT token
    $tokenPayload = [
        'username' => $username,
        'device_fp' => $deviceFingerprint,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60), // 24 hours
        'session_id' => bin2hex(random_bytes(16))
    ];
    
    $token = JWT::encode($tokenPayload);
    
    // Prepare response data
    $responseData = [
        'user' => [
            'username' => $username,
            'expiry_date' => $userData['expiry_date'] ?? 'N/A',
            'max_devices' => $userData['max_devices'] ?? 1,
            'current_devices' => $userData['current_devices'] ?? 1
        ],
        'token' => $token
    ];
    
    // Log successful login
    Security::logSecurityEvent('login_success', [
        'username' => $username,
        'device_fingerprint' => $deviceFingerprint
    ]);
    
    // Send success response
    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'timestamp' => time(),
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    // Log failed attempt
    if (isset($deviceFingerprint)) {
        Security::logSecurityEvent('login_failed', [
            'username' => $input['username'] ?? 'unknown',
            'device_fingerprint' => $deviceFingerprint,
            'error' => $e->getMessage()
        ]);
    }
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>
