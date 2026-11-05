<?php
declare(strict_types=1);

namespace App\Core;

final class Http
{
    public static function redirect(string $path): never
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function takeFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
