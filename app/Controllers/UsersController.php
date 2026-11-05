<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Http;
use App\Core\Input;
use App\Core\View;
use App\Services\AuditService;
use PDO;

final class UsersController
{
    public function index(): void
    {
        Auth::requireRole('owner');
        $users = Database::connection()->query(
            'SELECT id, name, username, role, is_active, created_at FROM users ORDER BY id'
        )->fetchAll(PDO::FETCH_ASSOC);
        View::render('users/index', [
            'users' => $users,
            'errors' => Http::takeFlash('errors', []),
            'success' => Http::takeFlash('success'),
            'old' => Http::takeFlash('old', []),
            'pageTitle' => 'إدارة المستخدمين',
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('owner');
        $name = trim(Input::string($_POST, 'name'));
        $username = trim(Input::string($_POST, 'username'));
        $password = Input::string($_POST, 'password');
        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'اسم المستخدم مطلوب وبحد أقصى 100 حرف.';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $errors[] = 'اسم الدخول يجب أن يتكون من 3 إلى 50 حرفًا إنجليزيًا أو رقمًا.';
        }
        if (strlen($password) < 12 || strlen($password) > 255) {
            $errors[] = 'كلمة المرور يجب ألا تقل عن 12 حرفًا.';
        }
        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::flash('old', ['name' => $name, 'username' => $username]);
            Http::redirect('/users');
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'INSERT INTO users (name, username, password_hash, role) VALUES (:name, :username, :password_hash, \'worker\')'
        );
        $pdo->beginTransaction();
        try {
            $statement->execute([
                'name' => $name,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            AuditService::record('user_created', 'users', (int) $pdo->lastInsertId(), [
                'username' => $username,
                'role' => 'worker',
            ]);
            $pdo->commit();
        } catch (\PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                Http::flash('errors', ['اسم الدخول مستخدم بالفعل.']);
                Http::flash('old', ['name' => $name, 'username' => $username]);
                Http::redirect('/users');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', 'تم إنشاء حساب العامل');
        Http::redirect('/users');
    }

    public function setActive(string $id): void
    {
        Auth::requireRole('owner');
        if ((int) $id === (int) Auth::user()['id']) {
            Http::flash('errors', ['لا يمكن إيقاف الحساب المستخدم حاليًا.']);
            Http::redirect('/users');
        }
        $active = Input::string($_POST, 'active') === '1' ? 1 : 0;
        $statement = Database::connection()->prepare(
            'UPDATE users SET is_active = :active WHERE id = :id AND role = \'worker\''
        );
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement->execute(['active' => $active, 'id' => (int) $id]);
            if ($statement->rowCount() === 0) {
                $pdo->rollBack();
                Http::flash('errors', ['لم يتم العثور على حساب عامل قابل للتعديل.']);
                Http::redirect('/users');
            }
            AuditService::record($active === 1 ? 'user_activated' : 'user_deactivated', 'users', (int) $id);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', $active === 1 ? 'تم تفعيل الحساب' : 'تم إيقاف الحساب');
        Http::redirect('/users');
    }
}
