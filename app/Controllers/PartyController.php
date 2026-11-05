<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Http;
use App\Core\Input;
use App\Core\View;
use App\Models\Party;
use App\Models\Transaction;
use App\Services\AuditService;
use App\Services\BalanceService;

final class PartyController
{
    public function index(): void
    {
        Auth::requireAuth();
        $search = trim(Input::string($_GET, 'q'));
        $filter = Input::string($_GET, 'filter', 'all');
        $includeInactive = (Auth::user()['role'] ?? '') === 'owner' && Input::string($_GET, 'status') === 'inactive';
        if (!in_array($filter, ['all', 'customers', 'suppliers', 'debtors'], true)) {
            $filter = 'all';
        }
        $page = filter_var(Input::string($_GET, 'page', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = $page === false ? 1 : $page;
        $perPage = 20;
        $page = min($page, intdiv(PHP_INT_MAX, $perPage));
        $result = Party::paginateWithBalances(
            $search,
            $filter,
            $includeInactive,
            $perPage,
            ($page - 1) * $perPage
        );
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = Party::paginateWithBalances(
                $search,
                $filter,
                $includeInactive,
                $perPage,
                ($page - 1) * $perPage
            );
        }
        View::render('parties/index', [
            'parties' => $result['items'],
            'search' => $search,
            'filter' => $filter,
            'includeInactive' => $includeInactive,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalParties' => $result['total'],
            'success' => Http::takeFlash('success'),
            'errors' => Http::takeFlash('errors', []),
            'pageTitle' => 'الأطراف',
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        View::render('parties/form', [
            'party' => null,
            'errors' => Http::takeFlash('errors', []),
            'old' => Http::takeFlash('old', []),
            'pageTitle' => 'إضافة طرف',
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = self::validatedData($_POST);
        $errors = $data['errors'];
        unset($data['errors']);
        if ($data['phone'] !== '' && Party::hasPhone($data['phone'])) {
            $errors[] = 'رقم الهاتف مسجل لطرف آخر.';
        }
        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::flash('old', $_POST);
            Http::redirect('/parties/create');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = Party::create($data);
            AuditService::record('party_created', 'parties', $id, ['name' => $data['name']]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', 'تمت إضافة الطرف');
        Http::redirect('/parties');
    }

    public function edit(string $id): void
    {
        Auth::requireRole('owner');
        $party = Party::find((int) $id);
        if ($party === null) {
            http_response_code(404);
            echo 'الطرف غير موجود.';
            return;
        }
        View::render('parties/form', [
            'party' => $party,
            'errors' => Http::takeFlash('errors', []),
            'old' => Http::takeFlash('old', []),
            'pageTitle' => 'تعديل طرف',
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('owner');
        $party = Party::find((int) $id);
        if ($party === null) {
            http_response_code(404);
            echo 'الطرف غير موجود.';
            return;
        }
        $data = self::validatedData($_POST);
        $errors = $data['errors'];
        unset($data['errors']);
        if ($data['phone'] !== '' && Party::hasPhone($data['phone'], (int) $id)) {
            $errors[] = 'رقم الهاتف مسجل لطرف آخر.';
        }
        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::flash('old', $_POST);
            Http::redirect('/parties/' . $id . '/edit');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Party::update((int) $id, $data);
            AuditService::record('party_updated', 'parties', (int) $id, [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'kind' => $data['kind'],
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', 'تم تحديث بيانات الطرف');
        Http::redirect('/parties');
    }

    public function opening(string $id): void
    {
        Auth::requireRole('owner');
        $party = Party::find((int) $id);
        $amount = self::parseMoney($_POST['amount'] ?? null);
        $direction = Input::string($_POST, 'direction');
        $note = trim(Input::string($_POST, 'note'));
        if ($party === null || (int) $party['is_active'] !== 1 || $amount === null || $amount <= 0
            || $amount > 1000000000 || !in_array($direction, ['debit', 'credit'], true) || mb_strlen($note) > 255) {
            Http::flash('errors', ['بيانات الرصيد الافتتاحي غير صحيحة.']);
            Http::redirect('/parties');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $transactionId = Transaction::create([
                'party_id' => (int) $id,
                'tx_date' => date('Y-m-d'),
                'type' => 'opening',
                'direction' => $direction,
                'weight' => null,
                'unit_price' => null,
                'amount' => $amount,
                'note' => $note === '' ? 'رصيد أول المدة' : $note,
                'group_id' => null,
                'created_by' => Auth::user()['id'],
            ]);
            AuditService::record('opening_balance_created', 'transactions', $transactionId, [
                'party_id' => (int) $id,
                'direction' => $direction,
                'amount' => $amount,
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', 'تمت إضافة الرصيد الافتتاحي');
        Http::redirect('/parties');
    }

    public function adjust(string $id): void
    {
        Auth::requireRole('owner');
        $party = Party::find((int) $id);
        $amount = self::parseMoney($_POST['amount'] ?? null);
        $direction = Input::string($_POST, 'direction');
        $note = trim(Input::string($_POST, 'note'));
        if ($party === null || (int) $party['is_active'] !== 1 || $amount === null || $amount <= 0
            || $amount > 1000000000 || !in_array($direction, ['debit', 'credit'], true)
            || $note === '' || mb_strlen($note) > 255) {
            Http::flash('errors', ['بيانات التسوية غير صحيحة؛ يجب كتابة سبب التسوية.']);
            Http::redirect('/parties');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $transactionId = Transaction::create([
                'party_id' => (int) $id,
                'tx_date' => date('Y-m-d'),
                'type' => 'adjust',
                'direction' => $direction,
                'weight' => null,
                'unit_price' => null,
                'amount' => $amount,
                'note' => $note,
                'group_id' => null,
                'created_by' => Auth::user()['id'],
            ]);
            AuditService::record('transaction_adjusted', 'transactions', $transactionId, [
                'party_id' => (int) $id,
                'direction' => $direction,
                'amount' => $amount,
                'note' => $note,
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', 'تمت إضافة التسوية');
        Http::redirect('/parties');
    }

    public function setActive(string $id): void
    {
        Auth::requireRole('owner');
        $party = Party::find((int) $id);
        if ($party === null) {
            http_response_code(404);
            echo 'الطرف غير موجود.';
            return;
        }
        $active = Input::string($_POST, 'active') === '1';
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Party::setActive((int) $id, $active);
            AuditService::record($active ? 'party_activated' : 'party_deactivated', 'parties', (int) $id);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        Http::flash('success', $active ? 'تم تفعيل الطرف' : 'تم إيقاف الطرف');
        Http::redirect('/parties');
    }

    public function balance(string $id): void
    {
        Auth::requireAuth();
        if (Party::find((int) $id) === null) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'الطرف غير موجود'], JSON_UNESCAPED_UNICODE);
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['balance' => BalanceService::forParty((int) $id)], JSON_THROW_ON_ERROR);
    }

    private static function validatedData(array $input): array
    {
        $name = trim(Input::string($input, 'name'));
        $phone = trim(Input::string($input, 'phone'));
        $kind = Input::string($input, 'kind', 'customer');
        $notes = trim(Input::string($input, 'notes'));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'اسم الطرف مطلوب وبحد أقصى 150 حرفًا.';
        }
        if (!preg_match('/^01[0125][0-9]{8}$/', $phone)) {
            $errors[] = 'أدخل رقم هاتف مصري صحيحًا مثل 01012345678.';
        }
        if (!in_array($kind, ['customer', 'supplier', 'both'], true)) {
            $errors[] = 'نوع الطرف غير صحيح.';
        }
        if (mb_strlen($notes) > 255) {
            $errors[] = 'الملاحظات يجب ألا تتجاوز 255 حرفًا.';
        }
        return [
            'name' => $name,
            'phone' => $phone,
            'kind' => $kind,
            'notes' => $notes === '' ? null : $notes,
            'errors' => $errors,
        ];
    }

    private static function parseMoney(mixed $value): ?int
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        $value = trim((string) $value);
        if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $value)) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}