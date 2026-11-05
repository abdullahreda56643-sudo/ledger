<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches): string {
            return '(?P<' . $matches[1] . '>[0-9]+)';
        }, $path);
        $this->routes[] = [$method, '#^' . $pattern . '/?$#', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = Url::requestPath($uri);
        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== strtoupper($method) || !preg_match($pattern, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, static fn ($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        echo 'الصفحة غير موجودة.';
    }
}