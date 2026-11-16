<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Unit
{
    private const SELECT = 'SELECT u.id, u.category_code, u.name, u.symbol, u.factor_to_base,
                                   u.is_base, u.is_default, u.is_active, u.sort_order,
                                   c.name AS category_name, c.base_unit_name, c.base_unit_symbol,
                                   c.sort_order AS category_order
                            FROM units u
                            JOIN unit_categories c ON c.code = u.category_code';

    public static function categories(): array
    {
        return Database::connection()->query(
            'SELECT code, name, base_unit_name, base_unit_symbol, sort_order
             FROM unit_categories ORDER BY sort_order, code'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function all(): array
    {
        return Database::connection()->query(
            self::SELECT . ' ORDER BY c.sort_order, u.sort_order, u.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function active(): array
    {
        return Database::connection()->query(
            self::SELECT . ' WHERE u.is_active = 1 ORDER BY c.sort_order, u.sort_order, u.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(self::SELECT . ' WHERE u.id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $unit = $statement->fetch(PDO::FETCH_ASSOC);
        return $unit ?: null;
    }

    public static function findActive(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            self::SELECT . ' WHERE u.id = :id AND u.is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $unit = $statement->fetch(PDO::FETCH_ASSOC);
        return $unit ?: null;
    }

    public static function defaultId(string $categoryCode): ?int
    {
        $statement = Database::connection()->prepare(
            'SELECT id FROM units WHERE category_code = :category_code AND is_active = 1 AND is_default = 1 LIMIT 1'
        );
        $statement->execute(['category_code' => $categoryCode]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function baseId(string $categoryCode): ?int
    {
        $statement = Database::connection()->prepare(
            'SELECT id FROM units WHERE category_code = :category_code AND is_active = 1 AND is_base = 1 LIMIT 1'
        );
        $statement->execute(['category_code' => $categoryCode]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO units (category_code, name, symbol, factor_to_base, is_base, is_default, is_active)
             VALUES (:category_code, :name, :symbol, :factor_to_base, 0, :is_default, 1)'
        );
        $statement->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE units
             SET name = :name, symbol = :symbol, factor_to_base = :factor_to_base,
                 is_active = :is_active, is_default = :is_default
             WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }
}
