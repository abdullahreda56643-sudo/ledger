<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Formatter;

final class UnitService
{
    private const QUANTITY_SCALE = 1000;
    private const FACTOR_SCALE = 1000000;
    private const BASE_SCALE = 1000000000;
    private const MAX_QUANTITY_MILLI = 1000000;
    private const MAX_FACTOR_MICRO = 1000000000000;
    private const MAX_AMOUNT = 1000000000;

    public static function calculate(string $quantityText, string $factorText, int $unitPrice): ?array
    {
        $normalizedFactor = self::normalizeFactor($factorText);
        if ($unitPrice <= 0 || $unitPrice > 10000000 || $normalizedFactor === null
            || !preg_match('/^(\d{1,7})(?:\.(\d{1,3}))?$/D', $quantityText, $quantityParts)
            || !preg_match('/^(\d{1,8})(?:\.(\d{1,6}))?$/D', $normalizedFactor, $factorParts)) {
            return null;
        }

        $quantityMilli = (int) $quantityParts[1] * self::QUANTITY_SCALE
            + (int) str_pad($quantityParts[2] ?? '', 3, '0');
        $factorMicro = (int) $factorParts[1] * self::FACTOR_SCALE
            + (int) str_pad($factorParts[2] ?? '', 6, '0');
        if ($quantityMilli < 1 || $quantityMilli > self::MAX_QUANTITY_MILLI
            || $factorMicro < 1 || $factorMicro > self::MAX_FACTOR_MICRO) {
            return null;
        }

        $scaledBaseQuantity = $quantityMilli * $factorMicro;
        $wholeBaseQuantity = intdiv($scaledBaseQuantity, self::BASE_SCALE);
        $remainder = $scaledBaseQuantity % self::BASE_SCALE;
        $amount = $wholeBaseQuantity * $unitPrice
            + intdiv($remainder * $unitPrice + intdiv(self::BASE_SCALE, 2), self::BASE_SCALE);
        if ($amount <= 0 || $amount > self::MAX_AMOUNT) {
            return null;
        }

        return [
            'quantity' => self::formatScaled($quantityMilli, self::QUANTITY_SCALE),
            'base_quantity' => self::formatScaled($scaledBaseQuantity, self::BASE_SCALE),
            'amount' => $amount,
        ];
    }

    public static function normalizeFactor(string $factorText): ?string
    {
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,6}))?$/D', trim($factorText), $parts)) {
            return null;
        }
        $factorMicro = (int) $parts[1] * self::FACTOR_SCALE
            + (int) str_pad($parts[2] ?? '', 6, '0');
        if ($factorMicro < 1 || $factorMicro > self::MAX_FACTOR_MICRO) {
            return null;
        }
        return self::formatScaled($factorMicro, self::FACTOR_SCALE);
    }

    public static function movementDescription(array $row): string
    {
        if ($row['quantity'] === null) {
            return '';
        }

        $unitSymbol = (string) ($row['unit_symbol'] ?? '');
        $baseSymbol = (string) ($row['base_unit_symbol'] ?? $unitSymbol);
        $description = self::formatQuantity((string) $row['quantity']) . ' ' . $unitSymbol
            . ' × ' . Formatter::money((int) $row['unit_price']) . ' جنيه/' . $baseSymbol;
        if ($baseSymbol !== $unitSymbol && $row['base_quantity'] !== null) {
            $description .= ' (≈ ' . self::formatQuantity((string) $row['base_quantity']) . ' ' . $baseSymbol . ')';
        }
        return $description;
    }

    public static function formatQuantity(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^-?\d+(?:\.\d+)?$/D', $value)) {
            return $value;
        }
        if (str_contains($value, '.')) {
            return rtrim(rtrim($value, '0'), '.');
        }
        return $value;
    }

    private static function formatScaled(int $value, int $scale): string
    {
        $whole = intdiv($value, $scale);
        $fraction = $value % $scale;
        $decimals = strlen((string) $scale) - 1;
        $formatted = $whole . '.' . str_pad((string) $fraction, $decimals, '0', STR_PAD_LEFT);
        return self::formatQuantity($formatted);
    }
}
