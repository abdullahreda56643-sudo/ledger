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
use App\Models\Unit;
use App\Services\AuditService;
use App\Services\OperationPolicy;
use App\Services\UnitService;

final class OperationController
{
    private const OPERATION_TYPES = ['sell', 'pay', 'supply', 'pay_supplier'];
    private const MAX_AMOUNT = 1000000000;

    public function index(): void
    {
        Auth::requireAuth();
        $today = date('Y-m-d');
        $from = Input::string($_GET, 'from', $today);
        $to = Input::string($_GET, 'to', $today);
        $fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $from) === 1
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $from)
            : false;
        $toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $to) === 1
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $to)
            : false;
        $errors = Http::takeFlash('errors', []);
        if (!$fromDate || !$toDate || $fromDate->format('Y-m-d') !== $from
            || $toDate->format('Y-m-d') !== $to || $from < '1000-01-01' || $to < '1000-01-01'
            || $from > $to || $to > $today) {
            $errors[] = 'حدد فترة زمنية صحيحة لا تتجاوز اليوم.';
            $from = $today;
            $to = $today;
        }

        $page = filter_var(Input::string($_GET, 'page', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = $page === false ? 1 : $page;
        $perPage = 50;
        $page = min($page, intdiv(PHP_INT_MAX, $perPage));
        $result = Transaction::paginateForPeriod($from, $to, $perPage, ($page - 1) * $perPage);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = Transaction::paginateForPeriod($from, $to, $perPage, ($page - 1) * $perPage);
        }

        View::render('operation/index', [
            'parties' => Party::active(),
            'units' => Unit::active(),
            'transactions' => $result['items'],
            'totalTransactions' => $result['total'],
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'errors' => $errors,
            'old' => Http::takeFlash('old', []),
            'success' => Http::takeFlash('success'),
            'pageTitle' => 'التشغيل اليومي',
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $old = $_POST;
        $errors = [];
        $type = Input::string($_POST, 'type');
        $partyId = filter_var($_POST['party_id'] ?? null, FILTER_VALIDATE_INT);
        $party = $partyId ? Party::find((int) $partyId) : null;
        if (!in_array($type, self::OPERATION_TYPES, true)) {
            $errors[] = 'نوع الحركة غير صحيح.';
        }
        if ($party === null || (int) $party['is_active'] !== 1) {
            $errors[] = 'اختر طرفًا نشطًا صحيحًا.';
        } elseif (in_array($type, self::OPERATION_TYPES, true)
            && !OperationPolicy::supports((string) $party['kind'], $type)) {
            $errors[] = 'نوع الحركة لا يتوافق مع نوع الطرف.';
        }

        $today = date('Y-m-d');
        $date = Input::string($_POST, 'tx_date', $today);
        $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) === 1
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $date)
            : false;
        if (!$validDate || $validDate->format('Y-m-d') !== $date || $date < '1000-01-01' || $date > $today) {
            $errors[] = 'تاريخ الحركة غير صحيح.';
        } elseif ((Auth::user()['role'] ?? '') === 'worker' && $date !== $today) {
            $errors[] = 'العامل يستطيع تسجيل حركات بتاريخ اليوم فقط.';
        }

        $quantity = null;
        $unitId = null;
        $unit = null;
        $unitSymbol = null;
        $baseQuantity = null;
        $baseUnitSymbol = null;
        $price = null;
        $amount = null;
        if (in_array($type, ['sell', 'supply'], true)) {
            $quantityText = trim(Input::string($_POST, 'quantity'));
            if (!preg_match('/^\d{1,7}(?:\.\d{1,3})?$/', $quantityText) || (float) $quantityText <= 0 || (float) $quantityText > 1000) {
                $errors[] = 'أدخل كمية صحيحة أكبر من صفر وبحد أقصى 1000 وحدة.';
            } else {
                $unitIdValue = filter_var($_POST['unit_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $unit = $unitIdValue === false ? null : Unit::findActive((int) $unitIdValue);
                if ($unit === null) {
                    $errors[] = 'اختر وحدة قياس نشطة.';
                } else {
                    $unitId = (int) $unit['id'];
                    $unitSymbol = (string) $unit['symbol'];
                    $baseUnitSymbol = (string) $unit['base_unit_symbol'];
                }
            }
            $price = self::parseMoney($_POST['unit_price'] ?? null);
            if ($price === null || $price <= 0 || $price > 10000000) {
                $baseUnitName = isset($unit['base_unit_name']) ? (string) $unit['base_unit_name'] : 'وحدة الأساس';
                $errors[] = 'أدخل سعرًا صحيحًا لكل ' . $baseUnitName . '.';
            }
            if ($unit !== null && $price !== null && $price > 0 && $price <= 10000000
                && preg_match('/^\d{1,7}(?:\.\d{1,3})?$/', $quantityText)) {
                $calculation = UnitService::calculate($quantityText, (string) $unit['factor_to_base'], $price);
                if ($calculation === null) {
                    $errors[] = 'تعذر حساب قيمة الحركة؛ تحقق من الكمية والسعر والوحدة المختارة.';
                } else {
                    $quantity = $calculation['quantity'];
                    $baseQuantity = $calculation['base_quantity'];
                    $amount = $calculation['amount'];
                }
            }
        } else {
            $amount = self::parseMoney($_POST['amount'] ?? null);
            if ($amount === null || $amount <= 0 || $amount > self::MAX_AMOUNT) {
                $errors[] = 'اكتب مبلغًا صحيحًا أكبر من صفر.';
            }
        }

        $payNow = null;
        if ($type === 'sell' && trim(Input::string($_POST, 'pay_now')) !== '') {
            $payNow = self::parseMoney($_POST['pay_now']);
            if ($payNow === null || $payNow <= 0 || $payNow > self::MAX_AMOUNT) {
                $errors[] = 'مبلغ الدفع الآن غير صحيح.';
            } elseif ($amount !== null && $payNow > $amount) {
                $errors[] = 'مبلغ الدفع الآن لا يمكن أن يتجاوز قيمة السحب.';
            }
        }
        $note = trim(Input::string($_POST, 'note'));
        if (mb_strlen($note) > 255) {
            $errors[] = 'الملاحظة يجب ألا تتجاوز 255 حرفًا.';
        }

        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::flash('old', $old);
            Http::redirect('/');
        }

        $direction = in_array($type, ['sell', 'pay_supplier'], true) ? 'debit' : 'credit';
        $groupId = $payNow !== null ? bin2hex(random_bytes(13)) : null;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $transactionId = Transaction::create([
                'party_id' => (int) $partyId,
                'tx_date' => $date,
                'type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_id' => $unitId,
                'unit_symbol' => $unitSymbol,
                'base_quantity' => $baseQuantity,
                'base_unit_symbol' => $baseUnitSymbol,
                'unit_price' => $price,
                'amount' => $amount,
                'note' => $note === '' ? null : $note,
                'group_id' => $groupId,
                'created_by' => Auth::user()['id'],
            ]);
            $paymentTransactionId = null;
            if ($payNow !== null) {
                $paymentTransactionId = Transaction::create([
                    'party_id' => (int) $partyId,
                    'tx_date' => $date,
                    'type' => 'pay',
                    'direction' => 'credit',
                    'quantity' => null,
                    'unit_id' => null,
                    'unit_symbol' => null,
                    'base_quantity' => null,
                    'base_unit_symbol' => null,
                    'unit_price' => null,
                    'amount' => $payNow,
                    'note' => 'دفع فوري مع السحب',
                    'group_id' => $groupId,
                    'created_by' => Auth::user()['id'],
                ]);
            }
            AuditService::record('transaction_created', 'transactions', $transactionId, [
                'type' => $type,
                'party_id' => (int) $partyId,
                'amount' => $amount,
                'group_id' => $groupId,
            ]);
            if ($paymentTransactionId !== null) {
                AuditService::record('transaction_created', 'transactions', $paymentTransactionId, [
                    'type' => 'pay',
                    'party_id' => (int) $partyId,
                    'amount' => $payNow,
                    'group_id' => $groupId,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        Http::flash('success', 'تم الحفظ');
        Http::redirect('/');
    }

    public function void(string $id): void
    {
        Auth::requireRole('owner');
        $reason = trim(Input::string($_POST, 'reason'));
        if (mb_strlen($reason) > 255) {
            Http::flash('errors', ['سبب الإلغاء يجب ألا يتجاوز 255 حرفًا.']);
            Http::redirect('/');
        }

        Transaction::void((int) $id, (int) Auth::user()['id'], $reason === '' ? null : $reason);
        Http::flash('success', 'تم إلغاء الحركة');
        Http::redirect('/');
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