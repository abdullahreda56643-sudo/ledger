<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'username' => (string) $user['username'],
            'role' => (string) $user['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Http::redirect('/login');
        }

        $statement = Database::connection()->prepare(
            'SELECT name, username, role, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => self::user()['id']]);
        $user = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!$user || (int) $user['is_active'] !== 1) {
            self::logout();
            Http::redirect('/login');
        }

        $_SESSION['user']['name'] = (string) $user['name'];
        $_SESSION['user']['username'] = (string) $user['username'];
        $_SESSION['user']['role'] = (string) $user['role'];
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if ((self::user()['role'] ?? '') !== $role) {
            http_response_code(403);
            echo 'ليس لديك صلاحية للوصول إلى هذه الصفحة.';
            exit;
        }
    }
}