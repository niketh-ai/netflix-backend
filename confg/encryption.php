<?php
class Encryption {
    private static $key;
    
    public static function init() {
        self::$key = base64_decode(API_ENCRYPTION_KEY);
        if (strlen(self::$key) !== 32) {
            throw new Exception('Encryption key must be 32 bytes after base64 decode');
        }
    }
    
    public static function encrypt($data) {
        self::init();
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt($encryptedData) {
        self::init();
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 2048,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>