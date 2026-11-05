<?php
declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function basePath(): string
    {
        $configured = $GLOBALS['config']['app']['base_path'] ?? '';
        if (is_string($configured) && trim($configured) !== '') {
            $basePath = '/' . trim(str_replace('\\', '/', $configured), '/');
            return $basePath === '/' ? '' : $basePath;
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName === '' || !str_starts_with($scriptName, '/')) {
            return '';
        }

        $directory = dirname($scriptName);
        return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    }

    public static function to(string $path): string
    {
        if (preg_match('#^(?:https?:)?//#i', $path) || str_starts_with($path, '#')) {
            return $path;
        }

        $route = '/' . ltrim($path, '/');
        return self::basePath() . ($route === '/' ? '/' : $route);
    }

    public static function requestPath(?string $uri = null): string
    {
        $path = parse_url($uri ?? (string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $basePath = self::basePath();

        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
            $path = $path === '' ? '/' : $path;
        }

        if ($path === '/index.php') {
            return '/';
        }

        return $path;
    }
}
