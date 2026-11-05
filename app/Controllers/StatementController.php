<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Input;
use App\Core\View;
use App\Models\Party;
use App\Services\StatementService;
use App\Services\WhatsAppService;

final class StatementController
{
    public function show(string $partyId): void
    {
        Auth::requireAuth();
        $party = Party::find((int) $partyId);
        if ($party === null) {
            http_response_code(404);
            echo 'الطرف غير موجود.';
            return;
        }
        [$from, $to] = self::period((int) $partyId);
        $statement = StatementService::forParty((int) $partyId, $from, $to);
        View::render('statement/show', [
            'party' => $party,
            'from' => $from,
            'to' => $to,
            'statement' => $statement,
            'errors' => Http::takeFlash('errors', []),
            'whatsappUrl' => WhatsAppService::link($party['phone'], $party['name'], $from, $to, $statement['closing_balance']),
            'pageTitle' => 'كشف حساب ' . $party['name'],
        ]);
    }

    public function print(string $partyId): void
    {
        Auth::requireAuth();
        $party = Party::find((int) $partyId);
        if ($party === null) {
            http_response_code(404);
            echo 'الطرف غير موجود.';
            return;
        }
        [$from, $to] = self::period((int) $partyId);
        $statement = StatementService::forParty((int) $partyId, $from, $to);
        View::render('statement/print', [
            'party' => $party,
            'from' => $from,
            'to' => $to,
            'statement' => $statement,
            'traderName' => $GLOBALS['config']['app']['trader_name'],
            'pageTitle' => 'طباعة كشف حساب',
        ], false);
    }

    private static function period(int $partyId): array
    {
        $today = new \DateTimeImmutable('today');
        $from = Input::string($_GET, 'from', $today->modify('-29 days')->format('Y-m-d'));
        $to = Input::string($_GET, 'to', $today->format('Y-m-d'));
        $fromDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        $toDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $to);
        if (!$fromDate || !$toDate || $fromDate->format('Y-m-d') !== $from
            || $toDate->format('Y-m-d') !== $to || $from > $to) {
            Http::flash('errors', ['حدد فترة زمنية صحيحة.']);
            Http::redirect('/statement/' . $partyId);
        }
        return [$from, $to];
    }
}