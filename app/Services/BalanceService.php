<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BalanceService
{
    public static function forParty(int $partyId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COALESCE(SUM(CASE direction WHEN \'debit\' THEN amount ELSE -amount END), 0)
             FROM transactions WHERE party_id = :party_id AND is_void = 0'
        );
        $statement->execute(['party_id' => $partyId]);
        return (int) $statement->fetchColumn();
    }
}