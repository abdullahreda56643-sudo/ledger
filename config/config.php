<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'app' => [
        'name' => $env('APP_NAME', 'دفتر الحساب'),
        'env' => $env('APP_ENV', 'production'),
        'debug' => $env('APP_DEBUG', 'false') === 'true',
        'timezone' => $env('APP_TIMEZONE', 'Africa/Cairo'),
        'trader_name' => $env('TRADER_NAME', 'دفتر الحساب'),
        'base_url' => rtrim($env('APP_URL', ''), '/'),
        'base_path' => $env('APP_BASE_PATH', ''),
    ],
    'database' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '3306'),
        'name' => $env('DB_NAME', 'ledger'),
        'user' => $env('DB_USER', 'covida'),
        'password' => $env('DB_PASSWORD', '123456'),
        'mysqldump' => $env('MYSQLDUMP_PATH', 'mysqldump'),
    ],
    'paths' => [
        'root' => $root,
        'storage' => $root . DIRECTORY_SEPARATOR . 'storage',
    ],
];
