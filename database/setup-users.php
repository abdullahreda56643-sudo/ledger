<?php
declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';
$GLOBALS['config'] = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$read = static function (string $prompt): string {
    fwrite(STDOUT, $prompt);
    $value = fgets(STDIN);
    return trim($value === false ? '' : $value);
};

$password = static function (string $username) use ($read): string {
    $value = getenv(strtoupper($username) . '_PASSWORD');
    if ($value === false || $value === '') {
        $value = $read("كلمة مرور {$username} (12 حرفًا على الأقل): ");
    }
    if (strlen($value) < 12 || strlen($value) > 255) {
        throw new RuntimeException('كلمة المرور يجب أن تكون بين 12 و255 حرفًا.');
    }
    return $value;
};

$pdo = Database::connection();
$insert = $pdo->prepare(
    'INSERT INTO users (name, username, password_hash, role)
     VALUES (:name, :username, :password_hash, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash),
                             role = VALUES(role), is_active = 1'
);
$audit = $pdo->prepare(
    'INSERT INTO audit_log (user_id, action, table_name, record_id, details)
     VALUES (:user_id, \'user_initialized\', \'users\', :record_id, :details)'
);

foreach ([
    ['name' => 'المالك', 'username' => 'owner', 'role' => 'owner'],
    ['name' => 'العامل', 'username' => 'worker', 'role' => 'worker'],
] as $account) {
    $hash = password_hash($password($account['username']), PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $insert->execute([
            'name' => $account['name'],
            'username' => $account['username'],
            'password_hash' => $hash,
            'role' => $account['role'],
        ]);
        $select = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $select->execute(['username' => $account['username']]);
        $id = (int) $select->fetchColumn();
        $audit->execute([
            'user_id' => $id,
            'record_id' => $id,
            'details' => json_encode(['role' => $account['role']], JSON_THROW_ON_ERROR),
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
    fwrite(STDOUT, 'تم تجهيز الحساب: ' . $account['username'] . PHP_EOL);
}
