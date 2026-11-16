<?php
declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';
$GLOBALS['config'] = require __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$configuredBasePath = $GLOBALS['config']['app']['base_path'] ?? '';
$basePath = \App\Core\Url::basePath();
$publicPath = is_string($configuredBasePath) && trim($configuredBasePath) !== ''
    ? ($basePath === '' ? '/' : $basePath . '/')
    : rtrim($basePath, '/') . '/public/';

header('Location: ' . $publicPath, true, 302);
exit;
