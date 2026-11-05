<?php
declare(strict_types=1);

namespace App\Core;

final class Input
{
    public static function string(array $source, string $key, string $default = ''): string
    {
        $value = $source[$key] ?? $default;
        return is_string($value) ? $value : '';
    }
}
