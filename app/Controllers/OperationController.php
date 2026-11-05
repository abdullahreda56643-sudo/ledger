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

final class OperationController
{
    private const OPERATION_TYPES = ['sell', 'pay', 'supply', 'pay_supplier'];
    private const MAX_AMOUNT = 1000000000;

    public function index(): void
    {
        Auth::requireAuth();
        View::render('operation/index', [
            'parties' => Party::active(),
            'transactions' => Transaction::today(),
            'errors' => Http::takeFlash('errors', []),
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
        }

        $today = date('Y-m-d');
        $date = Input::string($_POST, 'tx_date', $today);
        $validDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$validDate || $validDate->format('Y-m-d') !== $date || $date > $today) {
            $errors[] = 'تاريخ الحركة غير صحيح.';
        } elseif ((Auth::user()['role'] ?? '') === 'worker' && $date !== $today) {
            $errors[] = 'العامل يستطيع تسجيل حركات بتاريخ اليوم فقط.';
        }

        $weight = null;
        $price = null;
        $amount = null;
        if (in_array($type, ['sell', 'supply'], true)) {
            $weightText = trim(Input::string($_POST, 'weight'));
            if (!preg_match('/^\d{1,7}(?:\.\d{1,3})?$/', $weightText) || (float) $weightText <= 0 || (float) $weightText > 1000) {
                $errors[] = 'أدخل وزنًا صحيحًا أكبر من صفر وبحد أقصى 1000 كجم.';
            } else {
                [$wholeWeight, $fractionWeight] = array_pad(explode('.', $weightText, 2), 2, '');
                $weightMilli = (int) $wholeWeight * 1000 + (int) str_pad($fractionWeight, 3, '0');
                $weight = intdiv($weightMilli, 1000) . '.' . str_pad((string) ($weightMilli % 1000), 3, '0', STR_PAD_LEFT);
            }
            $price = self::parseMoney($_POST['unit_price'] ?? null);
            if ($price === null || $price <= 0 || $price > 10000000) {
                $errors[] = 'أدخل سعرًا صحيحًا للكيلو.';
            }
            if ($weight !== null && $price !== null) {
                $amount = intdiv($weightMilli * $price + 500, 1000);
                if ($amount <= 0 || $amount > self::MAX_AMOUNT) {
                    $errors[] = 'قيمة الحركة تتجاوز الحد المسموح.';
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
                'weight' => $weight,
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
                    'weight' => null,
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