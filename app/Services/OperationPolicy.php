<?php
declare(strict_types=1);

namespace App\Services;

final class OperationPolicy
{
    private const OPERATIONS_BY_PARTY_TYPE = [
        'customer' => ['sell', 'pay'],
        'supplier' => ['supply', 'pay_supplier'],
        'both' => ['sell', 'pay', 'supply', 'pay_supplier'],
    ];

    public static function operationsForPartyType(string $partyType): array
    {
        return self::OPERATIONS_BY_PARTY_TYPE[$partyType] ?? [];
    }

    public static function supports(string $partyType, string $operationType): bool
    {
        return in_array($operationType, self::operationsForPartyType($partyType), true);
    }
}
