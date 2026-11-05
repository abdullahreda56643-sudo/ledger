<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use PDO;

final class ReportController
{
    public function debtors(): void
    {
        Auth::requireRole('owner');
        $pdo = Database::connection();
        $summary = $pdo->query(
            'SELECT COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) AS owed_to_us,
                    COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END), 0) AS we_owe,
                    COALESCE(SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END), 0) AS debtors_count
             FROM (
                SELECT p.id, COALESCE(SUM(CASE WHEN t.is_void = 0 AND t.direction = \'debit\' THEN t.amount
                                               WHEN t.is_void = 0 AND t.direction = \'credit\' THEN -t.amount
                                               ELSE 0 END), 0) AS balance
                FROM parties p LEFT JOIN transactions t ON t.party_id = p.id
                WHERE p.is_active = 1 GROUP BY p.id
             ) balances'
        )->fetch(PDO::FETCH_ASSOC);
        $parties = $pdo->query(
            'SELECT p.id, p.name, p.phone,
                    COALESCE(SUM(CASE WHEN t.is_void = 0 AND t.direction = \'debit\' THEN t.amount
                                      WHEN t.is_void = 0 AND t.direction = \'credit\' THEN -t.amount
                                      ELSE 0 END), 0) AS balance
             FROM parties p LEFT JOIN transactions t ON t.party_id = p.id
             WHERE p.is_active = 1
             GROUP BY p.id HAVING balance > 0 ORDER BY balance DESC, p.name'
        )->fetchAll(PDO::FETCH_ASSOC);
        $maxBalance = $parties === [] ? 0 : (int) $parties[0]['balance'];
        View::render('reports/debtors', [
            'summary' => $summary,
            'parties' => $parties,
            'maxBalance' => $maxBalance,
            'pageTitle' => 'تقرير المديونية',
        ]);
    }
}