<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Party
{
    public static function active(): array
    {
        return Database::connection()->query(
            'SELECT id, name, phone, kind FROM parties WHERE is_active = 1 ORDER BY name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM parties WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $party = $statement->fetch(PDO::FETCH_ASSOC);
        return $party ?: null;
    }

    public static function paginateWithBalances(
        string $search,
        string $filter,
        bool $includeInactive,
        int $limit,
        int $offset
    ): array
    {
        $where = ['p.is_active = ' . ($includeInactive ? '0' : '1')];
        $params = [];
        if ($search !== '') {
            $where[] = '(p.name LIKE :search_name OR p.phone LIKE :search_phone)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_phone'] = '%' . $search . '%';
        }
        if ($filter === 'customers') {
            $where[] = "p.kind IN ('customer','both')";
        } elseif ($filter === 'suppliers') {
            $where[] = "p.kind IN ('supplier','both')";
        }

        $balanceQuery = 'SELECT p.id, p.name, p.phone, p.kind,
                    COALESCE(SUM(CASE WHEN t.is_void = 0 AND t.direction = \'debit\' THEN t.amount
                                      WHEN t.is_void = 0 AND t.direction = \'credit\' THEN -t.amount
                                      ELSE 0 END), 0) AS balance
                FROM parties p LEFT JOIN transactions t ON t.party_id = p.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY p.id';
        $balanceFilter = $filter === 'debtors' ? ' WHERE balance > 0' : '';
        $pdo = Database::connection();

        $countStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM (' . $balanceQuery . ') AS party_balances' . $balanceFilter
        );
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $statement = $pdo->prepare(
            'SELECT * FROM (' . $balanceQuery . ') AS party_balances'
            . $balanceFilter
            . ' ORDER BY balance DESC, name ASC, id ASC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO parties (name, phone, kind, notes) VALUES (:name, :phone, :kind, :notes)'
        );
        $statement->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE parties SET name = :name, phone = :phone, kind = :kind, notes = :notes WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $statement = Database::connection()->prepare('UPDATE parties SET is_active = :active WHERE id = :id');
        $statement->execute(['active' => (int) $active, 'id' => $id]);
    }

    public static function hasPhone(string $phone, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM parties WHERE phone = :phone';
        $params = ['phone' => $phone];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
    }
}