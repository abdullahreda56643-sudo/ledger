<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public static function findActiveByUsername(string $username): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, username, password_hash, role FROM users WHERE username = :username AND is_active = 1 LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}