<?php
declare(strict_types=1);

namespace App\Core;

final class Formatter
{
    public static function money(int|string $piasters): string
    {
        $amount = (int) $piasters;
        $decimals = abs($amount) % 100 === 0 ? 0 : 2;
        return number_format($amount / 100, $decimals, '.', ',');
    }

    public static function type(string $type): string
    {
        return [
            'sell' => 'سحب',
            'pay' => 'دفعة من الطرف',
            'supply' => 'توريد',
            'pay_supplier' => 'دفعة لمورد',
            'opening' => 'رصيد أول المدة',
            'adjust' => 'تسوية',
        ][$type] ?? $type;
    }

    public static function balanceLabel(int $balance): string
    {
        if ($balance > 0) {
            return 'عليه ' . self::money($balance);
        }
        if ($balance < 0) {
            return 'له ' . self::money(abs($balance));
        }
        return 'متزن';
    }

    public static function balanceClass(int $balance): string
    {
        return $balance > 0 ? 'text-ledger-red' : ($balance < 0 ? 'text-ledger-green' : 'text-ledger-muted');
    }
}
