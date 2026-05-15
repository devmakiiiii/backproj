<?php
namespace App\Utils;

class Encryption {
    private static string $key;
    private static bool $initialized = false;

    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        if (!defined('ENCRYPTION_KEY')) {
            throw new \Exception('ENCRYPTION_KEY not defined');
        }
        self::$key = ENCRYPTION_KEY;
        self::$initialized = true;
    }

    public static function encrypt(string $data): array {
        self::init();
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($data, 'AES-256-GCM', self::$key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }
        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }

    public static function decrypt(string $data, ?string $iv, ?string $tag): string|false {
        self::init();
        if ($iv === null || $tag === null || $iv === '' || $tag === '') {
            return $data;
        }
        $result = openssl_decrypt(
            base64_decode($data),
            'AES-256-GCM',
            self::$key,
            OPENSSL_RAW_DATA,
            base64_decode($iv),
            base64_decode($tag)
        );
        return $result;
    }
}