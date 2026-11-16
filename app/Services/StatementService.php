<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class StatementService
{
    public static function forParty(int $partyId, string $from, string $to): array
    {
        $pdo = Database::connection();
        $openingQuery = $pdo->prepare(
            'SELECT COALESCE(SUM(CASE direction WHEN \'debit\' THEN amount ELSE -amount END), 0)
             FROM transactions FORCE INDEX (idx_party_date)
             WHERE party_id = :party_id AND tx_date < :from_date AND is_void = 0'
        );
        $openingQuery->execute(['party_id' => $partyId, 'from_date' => $from]);
        $balance = (int) $openingQuery->fetchColumn();
        $openingBalance = $balance;

        $rowsQuery = $pdo->prepare(
            'SELECT id, tx_date, created_at, type, direction, quantity, unit_id, unit_symbol,
                    base_quantity, base_unit_symbol, unit_price, amount, note, is_void, void_reason
             FROM transactions FORCE INDEX (idx_party_date)
             WHERE party_id = :party_id AND tx_date >= :from_date AND tx_date <= :to_date
             ORDER BY tx_date ASC, id ASC'
        );
        $rowsQuery->execute(['party_id' => $partyId, 'from_date' => $from, 'to_date' => $to]);
        $rows = $rowsQuery->fetchAll(PDO::FETCH_ASSOC);
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($rows as &$row) {
            $row['balance_before'] = $balance;
            if ((int) $row['is_void'] === 0) {
                $amount = (int) $row['amount'];
                if ($row['direction'] === 'debit') {
                    $balance += $amount;
                    $totalDebit += $amount;
                } else {
                    $balance -= $amount;
                    $totalCredit += $amount;
                }
            }
            $row['running_balance'] = $balance;
        }
        unset($row);

        return [
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'rows' => $rows,
            'closing_balance' => $balance,
        ];
    }
}