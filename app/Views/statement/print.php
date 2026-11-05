<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$openingBalance = (int) $statement['opening_balance'];
$closingBalance = (int) $statement['closing_balance'];
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>كشف حساب <?= View::e($party['name']) ?> - <?= View::e($traderName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/print.css')) ?>">
</head>
<body class="print-page">
  <div class="print-toolbar" role="region" aria-label="خيارات التصدير">
    <p>من نافذة الطباعة اختر <strong>حفظ كـ PDF</strong> لتنزيل كشف الحساب.</p>
    <button type="button" class="print-button" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7zM17 11h.01"/></svg>
      <span>تصدير / طباعة</span>
    </button>
  </div>

  <main class="print-document">
    <header class="print-header">
      <div class="print-brand">
        <span class="print-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/><path d="M14 4v5h4m-8 4h4m-4 3h4"/></svg>
        </span>
        <div>
          <p class="print-trader"><?= View::e($traderName) ?></p>
          <span class="print-document-label">كشف حساب تفصيلي</span>
        </div>
      </div>
      <span class="print-date">تاريخ الإصدار: <?= View::e(date('Y-m-d')) ?></span>
    </header>

    <section class="print-party">
      <div>
        <span class="print-label">كشف حساب</span>
        <h1><?= View::e($party['name']) ?></h1>
      </div>
      <div class="print-party-meta">
        <?php if ($party['phone'] !== ''): ?><span>الهاتف: <?= View::e($party['phone']) ?></span><?php endif; ?>
        <span>الفترة: <?= View::e($from) ?> <b>—</b> <?= View::e($to) ?></span>
      </div>
    </section>

    <section class="print-balance-summary" aria-label="ملخص الرصيد">
      <div class="print-balance-item">
        <span>رصيد أول الفترة</span>
        <strong class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::balanceLabel($openingBalance)) ?></strong>
      </div>
      <div class="print-balance-item print-balance-closing">
        <span>صافي الرصيد</span>
        <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::balanceLabel($closingBalance)) ?> <small>جنيه</small></strong>
      </div>
    </section>

    <table class="print-table">
      <thead>
        <tr><th class="print-col-date">التاريخ</th><th>البيان</th><th class="print-col-amount">عليه</th><th class="print-col-amount">له</th><th class="print-col-balance">الرصيد</th></tr>
      </thead>
      <tbody>
        <tr class="opening">
          <td>—</td>
          <td>رصيد أول الفترة</td>
          <td>—</td>
          <td>—</td>
          <td class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::balanceLabel($openingBalance)) ?></td>
        </tr>
        <?php foreach ($statement['rows'] as $row): ?>
          <?php $void = (int) $row['is_void'] === 1; $balance = (int) $row['running_balance']; ?>
          <tr class="<?= $void ? 'void' : '' ?>">
            <td class="print-nowrap"><?= View::e($row['tx_date']) ?></td>
            <td class="print-description">
              <span><?= View::e(Formatter::type($row['type'])) ?></span>
              <?php if ($row['weight'] !== null): ?><small><?= View::e($row['weight']) ?> كجم × <?= View::e(Formatter::money($row['unit_price'])) ?> جنيه</small><?php endif; ?>
              <?php if ($row['note']): ?><small><?= View::e($row['note']) ?></small><?php endif; ?>
              <?php if ($void): ?><small class="print-void-label">حركة ملغاة</small><?php endif; ?>
            </td>
            <td class="print-nowrap"><?= !$void && $row['direction'] === 'debit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
            <td class="print-nowrap"><?= !$void && $row['direction'] === 'credit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
            <td class="<?= Formatter::balanceClass($balance) ?>"><?= View::e(Formatter::balanceLabel($balance)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <footer class="print-footer">
      <span>نهاية كشف الحساب</span>
      <span>الرصيد الختامي: <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::balanceLabel($closingBalance)) ?> جنيه</strong></span>
    </footer>
  </main>
  <script>
    window.addEventListener('load', () => {
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => window.setTimeout(() => window.print(), 150));
        return;
      }
      window.setTimeout(() => window.print(), 400);
    });
  </script>
</body>
</html>
