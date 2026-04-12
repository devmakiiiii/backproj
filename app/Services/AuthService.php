<?php
namespace App\Services;

use Firebase\JWT\JWT;

class AuthService {
    public static function verifyToken() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return false;
        }
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        try {
            $decoded = JWT::decode($token, $_ENV['JWT_SECRET'], ['HS256']);
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}