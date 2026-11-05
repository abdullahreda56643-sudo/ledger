<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Http;
use App\Core\Input;
use App\Core\View;
use App\Models\User;
use App\Services\AuditService;
use PDO;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Http::redirect('/');
        }
        View::render('auth/login', [
            'error' => Http::takeFlash('error'),
            'old' => Http::takeFlash('old', []),
            'pageTitle' => 'تسجيل الدخول',
        ], false);
    }

    public function login(): void
    {
        if (Auth::check()) {
            Http::redirect('/');
        }
        $username = trim(Input::string($_POST, 'username'));
        $password = Input::string($_POST, 'password');
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');

        $throttle = $pdo->prepare(
            'SELECT COUNT(*) AS attempts, MAX(attempted_at) AS latest
             FROM login_attempts WHERE ip = :ip AND username = :username
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)'
        );
        $throttle->execute(['ip' => $ip, 'username' => substr($username, 0, 50)]);
        $attempts = $throttle->fetch(PDO::FETCH_ASSOC);
        if ((int) ($attempts['attempts'] ?? 0) >= 5) {
            Http::flash('error', 'محاولات كثيرة. حاول مرة أخرى بعد 10 دقائق.');
            Http::flash('old', ['username' => $username]);
            Http::redirect('/login');
        }

        $user = User::findActiveByUsername(substr($username, 0, 50));
        if ($user === null || strlen($password) > 255 || !password_verify($password, $user['password_hash'])) {
            $insert = $pdo->prepare('INSERT INTO login_attempts (ip, username) VALUES (:ip, :username)');
            $insert->execute(['ip' => $ip, 'username' => substr($username, 0, 50)]);
            AuditService::record('login_failed', 'users', $user === null ? null : (int) $user['id'], [
                'username' => substr($username, 0, 50),
            ]);
            Http::flash('error', 'اسم المستخدم أو كلمة المرور غير صحيحة.');
            Http::flash('old', ['username' => $username]);
            Http::redirect('/login');
        }

        Auth::login($user);
        $clear = $pdo->prepare('DELETE FROM login_attempts WHERE ip = :ip AND username = :username');
        $clear->execute(['ip' => $ip, 'username' => substr($username, 0, 50)]);
        AuditService::record('login', 'users', (int) $user['id']);
        Http::redirect('/');
    }

    public function logout(): void
    {
        Auth::requireAuth();
        AuditService::record('logout', 'users', (int) Auth::user()['id']);
        Auth::logout();
        Http::redirect('/login');
    }
}