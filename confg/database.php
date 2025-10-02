<?php
class DatabaseConfig {
    private static $config = null;
    
    public static function load() {
        if (self::$config === null) {
            self::$config = [
                'DB_HOST' => getenv('SUPABASE_DB_HOST') ?: 'db.vfxnvqissazojbebkitb.supabase.co',
                'DB_USER' => getenv('SUPABASE_DB_USER') ?: 'postgres',
                'DB_PASS' => getenv('SUPABASE_DB_PASS') ?: 'nnrididjdjudud',
                'DB_NAME' => getenv('SUPABASE_DB_NAME') ?: 'postgres',
                'JWT_SECRET' => getenv('JWT_SECRET') ?: '1A2C23F0F249C0A696D2E1D485B3F5B956FC509689B94F0CA364E305B4F737EF',
                'API_RATE_LIMIT' => getenv('API_RATE_LIMIT') ?: 5,
                'API_RATE_WINDOW' => getenv('API_RATE_WINDOW') ?: 300,
                'API_ENCRYPTION_KEY' => getenv('API_ENCRYPTION_KEY') ?: 'waJ8kUyJGvO30Ll6/H70Dt2KkhtdTW9MnI58oMv6EZc=',
                'API_KEY' => getenv('API_KEY') ?: 'c876b9b79d78a35eda09c3a3d25fcebdb07819cc9cb3edaeafd1c4a6e120ab27'
            ];
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        $config = self::load();
        return $config[$key] ?? $default;
    }
}

// Load configuration
$config = DatabaseConfig::load();

// Define constants
define('DB_HOST', DatabaseConfig::get('DB_HOST'));
define('DB_USER', DatabaseConfig::get('DB_USER'));
define('DB_PASS', DatabaseConfig::get('DB_PASS'));
define('DB_NAME', DatabaseConfig::get('DB_NAME'));
define('JWT_SECRET', DatabaseConfig::get('JWT_SECRET'));
define('API_RATE_LIMIT', (int)DatabaseConfig::get('API_RATE_LIMIT', 5));
define('API_RATE_WINDOW', (int)DatabaseConfig::get('API_RATE_WINDOW', 300));
define('API_ENCRYPTION_KEY', DatabaseConfig::get('API_ENCRYPTION_KEY'));
define('API_KEY', DatabaseConfig::get('API_KEY'));

// Create PostgreSQL connection
try {
    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=5432;dbname=" . DB_NAME . ";",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database service unavailable']);
    exit;
}

// Database helper functions for Supabase
function dbGetUser($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function dbGetConfig() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM app_settings WHERE is_public = true");
    $stmt->execute();
    $settings = $stmt->fetchAll();
    
    $config = [];
    foreach ($settings as $setting) {
        $config[$setting['setting_key']] = $setting['setting_value'];
    }
    return $config;
}

function dbGetAnnouncements() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        WHERE is_active = true AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY 
            CASE priority 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2  
                WHEN 'low' THEN 3
                ELSE 4
            END,
            created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function dbUpdateUserDevices($username, $deviceFingerprint) {
    global $pdo;
    
    // Get current user
    $user = dbGetUser($username);
    if (!$user) return false;
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE username = ?");
    $stmt->execute([$username]);
    
    return true;
}

function dbCreateUserSession($userId, $sessionData) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_token, device_fingerprint, ip_address, user_agent, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $userId,
        $sessionData['token'],
        $sessionData['device_fingerprint'],
        $sessionData['ip_address'],
        $sessionData['user_agent'],
        $sessionData['expires_at']
    ]);
}
?>
