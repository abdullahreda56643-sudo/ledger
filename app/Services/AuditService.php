<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class AuditService
{
    public static function record(string $action, ?string $table = null, ?int $recordId = null, array $details = []): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO audit_log (user_id, action, table_name, record_id, details, ip)
             VALUES (:user_id, :action, :table_name, :record_id, :details, :ip)'
        );
        $statement->execute([
            'user_id' => Auth::user()['id'] ?? null,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'details' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
