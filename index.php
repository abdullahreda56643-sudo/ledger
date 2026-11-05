<?php
declare(strict_types=1);

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = rtrim(dirname($scriptName), '/');

header('Location: ' . $basePath . '/public/', true, 302);
exit;
