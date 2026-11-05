<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function verifyRequest(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (!is_string($token) || !self::validate($token)) {
            http_response_code(419);
            echo 'انتهت صلاحية الطلب. أعد تحميل الصفحة وحاول مرة أخرى.';
            exit;
        }
    }
}