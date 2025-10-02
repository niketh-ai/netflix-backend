<?php
class JWT {
    public static function encode($payload, $key = null) {
        $key = $key ?: DatabaseConfig::get('JWT_SECRET');
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        
        $header_encoded = self::base64UrlEncode(json_encode($header));
        $payload_encoded = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $key, true);
        $signature_encoded = self::base64UrlEncode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    public static function decode($token, $key = null) {
        $key = $key ?: DatabaseConfig::get('JWT_SECRET');
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        $signature = self::base64UrlDecode($signature_encoded);
        $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $key, true);
        
        if (!hash_equals($signature, $expected_signature)) {
            throw new Exception('Invalid token signature');
        }
        
        $payload = json_decode(self::base64UrlDecode($payload_encoded), true);
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        return $payload;
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
?>
