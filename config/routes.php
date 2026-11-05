<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AuditController;
use App\Controllers\OperationController;
use App\Controllers\PartyController;
use App\Controllers\ReportController;
use App\Controllers\StatementController;
use App\Core\Router;
use App\Controllers\UsersController;

return static function (Router $router): void {
    $auth = new AuthController();
    $operation = new OperationController();
    $party = new PartyController();
    $statement = new StatementController();
    $report = new ReportController();
    $users = new UsersController();
    $audit = new AuditController();

    $router->get('/login', [$auth, 'showLogin']);
    $router->post('/login', [$auth, 'login']);
    $router->post('/logout', [$auth, 'logout']);
    $router->get('/', [$operation, 'index']);
    $router->post('/transactions', [$operation, 'store']);
    $router->post('/transactions/{id}/void', [$operation, 'void']);
    $router->get('/api/parties/{id}/balance', [$party, 'balance']);
    $router->get('/parties', [$party, 'index']);
    $router->get('/parties/create', [$party, 'create']);
    $router->post('/parties', [$party, 'store']);
    $router->get('/parties/{id}/edit', [$party, 'edit']);
    $router->post('/parties/{id}', [$party, 'update']);
    $router->post('/parties/{id}/opening', [$party, 'opening']);
    $router->post('/parties/{id}/adjust', [$party, 'adjust']);
    $router->post('/parties/{id}/active', [$party, 'setActive']);
    $router->get('/statement/{id}/print', [$statement, 'print']);
    $router->get('/statement/{id}', [$statement, 'show']);
    $router->get('/reports/debtors', [$report, 'debtors']);
    $router->get('/users', [$users, 'index']);
    $router->post('/users', [$users, 'store']);
    $router->post('/users/{id}/active', [$users, 'setActive']);
    $router->get('/audit', [$audit, 'index']);
};