<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Router;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';
$GLOBALS['config'] = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
date_default_timezone_set($GLOBALS['config']['app']['timezone']);
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=utf-8');

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax',
]);
session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    Csrf::verifyRequest();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$router = new Router();
$registerRoutes = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
$registerRoutes($router);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    $logDirectory = $GLOBALS['config']['paths']['storage'] . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0770, true) && !is_dir($logDirectory)) {
        error_log('Unable to create application log directory.');
    }
    $logFile = $logDirectory . DIRECTORY_SEPARATOR . 'app.log';
    if (!error_log((string) $exception . PHP_EOL, 3, $logFile)) {
        error_log((string) $exception);
    }
    http_response_code(500);
    if ($GLOBALS['config']['app']['debug']) {
        echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    } else {
        echo 'حدث خطأ. يرجى المحاولة لاحقًا.';
    }
}