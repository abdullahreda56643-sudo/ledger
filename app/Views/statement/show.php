<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;
?>
<section class="surface rounded-card border border-border bg-surface p-4 sm:p-6">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <h2 class="text-[17px] font-bold"><?= View::e($party['name']) ?></h2>
      <p class="text-sm text-ledger-muted"><?= View::e($party['phone']) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a target="_blank" rel="noopener noreferrer" href="<?= View::e($whatsappUrl) ?>" class="flex min-h-11 items-center rounded-lg bg-ledger-green px-4 text-sm font-bold text-white">إرسال واتساب</a>
      <a target="_blank" rel="noopener noreferrer" href="<?= View::e(Url::to('/statement/' . (int) $party['id'] . '/print?from=' . rawurlencode($from) . '&to=' . rawurlencode($to))) ?>" class="flex min-h-11 items-center gap-2 rounded-lg border border-ledger-border bg-white px-4 text-sm font-bold">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7zM17 11h.01"/></svg>
        <span>تصدير PDF</span>
      </a>
    </div>
  </div>
  <form method="get" class="my-5 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    <label class="text-sm font-bold">من<input type="date" name="from" value="<?= View::e($from) ?>" required class="mt-1 min-h-11 w-full rounded-lg border border-ledger-border px-2 font-normal"></label>
    <label class="text-sm font-bold">إلى<input type="date" name="to" value="<?= View::e($to) ?>" required class="mt-1 min-h-11 w-full rounded-lg border border-ledger-border px-2 font-normal"></label>
    <button class="min-h-11 rounded-lg border border-ledger-border px-4">عرض الفترة</button>
  </form>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[700px] text-right text-sm">
      <thead><tr class="border-b border-border"><th class="py-3">التاريخ</th><th>البيان</th><th>عليه</th><th>له</th><th>الرصيد</th></tr></thead>
      <tbody>
        <tr class="border-b border-ledger-border bg-ledger-bg font-bold"><td class="py-3">—</td><td>رصيد أول الفترة</td><td></td><td></td><td><?= View::e(Formatter::balanceLabel((int) $statement['opening_balance'])) ?></td></tr>
        <?php foreach ($statement['rows'] as $row): ?>
          <?php $void = (int) $row['is_void'] === 1; $balance = (int) $row['running_balance']; ?>
          <tr class="border-b border-ledger-border <?= $void ? 'opacity-50 line-through' : '' ?> <?= $row['tx_date'] === date('Y-m-d') ? 'bg-green-50/60' : '' ?>">
            <td class="py-3"><?= View::e($row['tx_date']) ?><?= $row['tx_date'] === date('Y-m-d') ? '<span class="mr-1 text-xs text-muted">النهارده</span>' : '' ?></td>
            <td><?= View::e(Formatter::type($row['type'])) ?><?php if ($row['weight'] !== null): ?> · <?= View::e($row['weight']) ?> × <?= View::e(Formatter::money($row['unit_price'])) ?><?php endif; ?><?php if ($row['note']): ?><span class="block text-xs text-ledger-muted"><?= View::e($row['note']) ?></span><?php endif; ?><?php if ($void): ?><span class="mr-1 text-xs">ملغاة</span><?php endif; ?></td>
            <td><?= !$void && $row['direction'] === 'debit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
            <td><?= !$void && $row['direction'] === 'credit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
            <td class="<?= Formatter::balanceClass($balance) ?>"><?= View::e(Formatter::balanceLabel($balance)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-5 rounded-lg bg-ledger-bg p-4 text-left text-lg font-extrabold <?= Formatter::balanceClass((int) $statement['closing_balance']) ?>">
    صافي الرصيد: <?= View::e(Formatter::balanceLabel((int) $statement['closing_balance'])) ?> جنيه
  </div>
</section>