<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$openingBalance = (int) $statement['opening_balance'];
$closingBalance = (int) $statement['closing_balance'];
?>
<section class="statement-screen">
  <header class="statement-heading">
    <div class="statement-party">
      <span class="statement-party-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.25"/><path d="M4.5 20v-1.5a4 4 0 0 1 4-4h7a4 4 0 0 1 4 4V20Z"/></svg>
      </span>
      <div class="statement-party-copy">
        <span class="statement-eyebrow">كشف حساب تفصيلي</span>
        <h2><?= View::e($party['name']) ?></h2>
        <?php if ($party['phone'] !== ''): ?><p><?= View::e($party['phone']) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="statement-actions">
      <a target="_blank" rel="noopener noreferrer" href="<?= View::e($whatsappUrl) ?>" class="statement-action statement-action-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4A8 8 0 1 1 20 11.5Z"/><path d="M9 9.5c.5 2 2 3.5 4 4l1-1 2 1c-.2 1-1 1.5-2 1.5-3.5-.5-6-3-6.5-6.5 0-1 .5-1.8 1.5-2l1 2Z"/></svg>
        <span>إرسال واتساب</span>
      </a>
      <a target="_blank" rel="noopener noreferrer" href="<?= View::e(Url::to('/statement/' . (int) $party['id'] . '/print?from=' . rawurlencode($from) . '&to=' . rawurlencode($to))) ?>" class="statement-action">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7zM17 11h.01"/></svg>
        <span>طباعة / PDF</span>
      </a>
    </div>
  </header>

  <section class="statement-period" aria-labelledby="statement-period-title">
    <div class="statement-section-heading">
      <div>
        <h3 id="statement-period-title">الفترة الزمنية</h3>
        <p>حدّد بداية ونهاية الفترة التي تريد مراجعة حركاتها.</p>
      </div>
      <span class="statement-period-range"><?= View::e($from) ?> <b>—</b> <?= View::e($to) ?></span>
    </div>
    <form method="get" class="statement-filter">
      <label class="statement-field">من تاريخ
        <input type="date" name="from" value="<?= View::e($from) ?>" required>
      </label>
      <label class="statement-field">إلى تاريخ
        <input type="date" name="to" value="<?= View::e($to) ?>" required>
      </label>
      <button type="submit" class="statement-action statement-filter-button">عرض الكشف</button>
    </form>
  </section>

  <section class="statement-ledger" aria-labelledby="statement-ledger-title">
    <div class="statement-section-heading statement-ledger-heading">
      <div>
        <h3 id="statement-ledger-title">الحركات المالية</h3>
        <p>تفاصيل الحركات وأثرها على رصيد الطرف.</p>
      </div>
      <span class="statement-count">عدد الحركات: <?= count($statement['rows']) ?></span>
    </div>
    <div class="statement-table-wrap">
      <table class="statement-table">
        <thead>
          <tr>
            <th scope="col" class="statement-date-column">التاريخ</th>
            <th scope="col">البيان</th>
            <th scope="col" class="statement-amount-column">عليه</th>
            <th scope="col" class="statement-amount-column">له</th>
            <th scope="col" class="statement-balance-column">الرصيد</th>
          </tr>
        </thead>
        <tbody>
          <tr class="statement-opening">
            <td>—</td>
            <td>رصيد أول الفترة</td>
            <td>—</td>
            <td>—</td>
            <td class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::balanceLabel($openingBalance)) ?></td>
          </tr>
          <?php if ($statement['rows'] === []): ?>
            <tr class="statement-empty">
              <td colspan="5">لا توجد حركات خلال هذه الفترة.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($statement['rows'] as $row): ?>
            <?php
              $void = (int) $row['is_void'] === 1;
              $balance = (int) $row['running_balance'];
              $isToday = $row['tx_date'] === date('Y-m-d');
            ?>
            <tr class="<?= $void ? 'statement-void' : '' ?> <?= $isToday && !$void ? 'statement-today' : '' ?>">
              <td class="statement-nowrap">
                <?= View::e($row['tx_date']) ?>
                <?php if ($isToday): ?><span class="statement-today-label">اليوم</span><?php endif; ?>
              </td>
              <td class="statement-description">
                <span class="statement-transaction-type"><?= View::e(Formatter::type($row['type'])) ?></span>
                <?php if ($row['weight'] !== null): ?><small><?= View::e($row['weight']) ?> كجم × <?= View::e(Formatter::money($row['unit_price'])) ?> جنيه</small><?php endif; ?>
                <?php if ($row['note']): ?><small><?= View::e($row['note']) ?></small><?php endif; ?>
                <?php if ($void): ?><small class="statement-void-label">حركة ملغاة</small><?php endif; ?>
              </td>
              <td class="statement-nowrap"><?= !$void && $row['direction'] === 'debit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
              <td class="statement-nowrap"><?= !$void && $row['direction'] === 'credit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
              <td class="<?= Formatter::balanceClass($balance) ?>"><?= View::e(Formatter::balanceLabel($balance)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <footer class="statement-closing">
      <span>صافي الرصيد</span>
      <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::balanceLabel($closingBalance)) ?> <small>جنيه</small></strong>
    </footer>
  </section>
</section>
