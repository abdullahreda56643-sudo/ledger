<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\AuditService;
use PDO;

final class Transaction
{
    public const TYPES = ['sell', 'pay', 'supply', 'pay_supplier', 'opening', 'adjust'];

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO transactions
                (party_id, tx_date, type, direction, weight, unit_price, amount, note, group_id, created_by)
             VALUES
                (:party_id, :tx_date, :type, :direction, :weight, :unit_price, :amount, :note, :group_id, :created_by)'
        );
        $statement->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function today(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.*, p.name AS party_name, u.name AS creator_name
             FROM transactions t
             JOIN parties p ON p.id = t.party_id
             JOIN users u ON u.id = t.created_by
             WHERE t.tx_date = :today
             ORDER BY t.id DESC'
        );
        $statement->execute(['today' => date('Y-m-d')]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $transaction = $statement->fetch(PDO::FETCH_ASSOC);
        return $transaction ?: null;
    }

    public static function void(int $id, int $userId, ?string $reason): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('SELECT * FROM transactions WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            $transaction = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$transaction || (int) $transaction['is_void'] === 1) {
                throw new \DomainException('الحركة غير موجودة أو ملغاة بالفعل.');
            }

            if ($transaction['group_id'] !== null) {
                $statement = $pdo->prepare(
                    'UPDATE transactions SET is_void = 1, voided_by = :user_id, voided_at = NOW(), void_reason = :reason
                     WHERE group_id = :group_id AND is_void = 0'
                );
                $statement->execute([
                    'user_id' => $userId,
                    'reason' => $reason,
                    'group_id' => $transaction['group_id'],
                ]);
            } else {
                $statement = $pdo->prepare(
                    'UPDATE transactions SET is_void = 1, voided_by = :user_id, voided_at = NOW(), void_reason = :reason
                     WHERE id = :id AND is_void = 0'
                );
                $statement->execute(['user_id' => $userId, 'reason' => $reason, 'id' => $id]);
            }
            AuditService::record('transaction_voided', 'transactions', $id, [
                'party_id' => (int) $transaction['party_id'],
                'group_id' => $transaction['group_id'],
                'reason' => $reason,
            ]);
            $pdo->commit();
            return $transaction;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}