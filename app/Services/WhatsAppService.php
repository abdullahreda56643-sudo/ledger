<?php
declare(strict_types=1);

namespace App\Services;

final class WhatsAppService
{
    public static function link(string $phone, string $partyName, string $from, string $to, int $balance): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (!preg_match('/^01[0125][0-9]{8}$/', $digits)) {
            throw new \InvalidArgumentException('رقم الهاتف غير صالح لإرسال كشف الحساب.');
        }

        $international = '20' . substr($digits, 1);
        $amount = number_format(abs($balance) / 100, 2, '.', ',');
        $state = $balance > 0 ? 'عليه' : ($balance < 0 ? 'له' : 'متزن');
        $text = "كشف حساب {$partyName}\nالفترة: {$from} إلى {$to}\nصافي الرصيد: {$state} {$amount} جنيه";
        return 'https://wa.me/' . $international . '?text=' . rawurlencode($text);
    }
}