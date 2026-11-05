<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$config = require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
$database = $config['database'];
$backupDirectory = $config['paths']['storage'] . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0770, true) && !is_dir($backupDirectory)) {
    throw new RuntimeException('تعذر إنشاء مجلد النسخ الاحتياطية.');
}

$dumpPath = getenv('MYSQLDUMP_PATH') ?: 'mysqldump';
$file = $backupDirectory . DIRECTORY_SEPARATOR . 'ledger-' . date('Ymd-His') . '.sql';
$command = [
    $dumpPath,
    '--host=' . $database['host'],
    '--port=' . $database['port'],
    '--user=' . $database['user'],
    '--single-transaction',
    '--routines',
    '--result-file=' . $file,
    $database['name'],
];

$previousPassword = getenv('MYSQL_PWD');
if ($database['password'] !== '') {
    putenv('MYSQL_PWD=' . $database['password']);
}
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
if ($process === false) {
    if ($previousPassword !== false) {
        putenv('MYSQL_PWD=' . $previousPassword);
    } else {
        putenv('MYSQL_PWD');
    }
    throw new RuntimeException('تعذر تشغيل أداة mysqldump.');
}
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);
if ($previousPassword !== false) {
    putenv('MYSQL_PWD=' . $previousPassword);
} else {
    putenv('MYSQL_PWD');
}
if ($exitCode !== 0 || !is_file($file) || filesize($file) === 0) {
    if (is_file($file)) {
        unlink($file);
    }
    throw new RuntimeException('فشل إنشاء النسخة الاحتياطية: ' . trim((string) $stderr . ' ' . (string) $stdout));
}

$backups = glob($backupDirectory . DIRECTORY_SEPARATOR . 'ledger-*.sql') ?: [];
usort($backups, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
foreach (array_slice($backups, 30) as $oldBackup) {
    if (!unlink($oldBackup)) {
        throw new RuntimeException('تعذر حذف نسخة احتياطية قديمة: ' . basename($oldBackup));
    }
}
fwrite(STDOUT, 'تم حفظ النسخة الاحتياطية: ' . basename($file) . PHP_EOL);
