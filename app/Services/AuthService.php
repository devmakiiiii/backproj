<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService {
    public static function verifyToken() {
        $auth = null;
        
        // Try multiple methods to get the Authorization header
        // 1. Standard HTTP_AUTHORIZATION (works with our htaccess fix)
        $auth = $_SERVER["HTTP_AUTHORIZATION"] ?? null;
        
        // 2. REDIRECT_HTTP_AUTHORIZATION (set by Apache mod_rewrite when using RewriteRule)
        if (!$auth) {
            $auth = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? null;
        }
        
        // 3. Check all REDIRECT_ prefixed headers
        if (!$auth) {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, "REDIRECT_HTTP_AUTHORIZATION") !== false && $value) {
                    $auth = $value;
                    break;
                }
            }
        }
        
        // 4. getenv as fallback
        if (!$auth) {
            $auth = getenv("HTTP_AUTHORIZATION");
        }
        
        // 5. apache_request_headers as last resort
        if (!$auth && function_exists("apache_request_headers")) {
            $headers = apache_request_headers();
            $auth = $headers["Authorization"] ?? null;
        }
        
        error_log("Auth header found: " . ($auth ? "yes" : "no"));
        
        if (!$auth) {
            return false;
        }
        
        $token = str_replace("Bearer ", "", $auth);
        
        if (empty($token)) {
            error_log("Empty token after Bearer removal");
            return false;
        }
        
        try {
            $decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET"], "HS256"));
            error_log("JWT decoded successfully for user_id: " . ($decoded->user_id ?? "unknown"));
            return $decoded;
        } catch (\Exception $e) {
            error_log("JWT decode error: " . $e->getMessage());
            return false;
        }
    }
}
