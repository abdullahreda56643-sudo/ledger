<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;
use App\Services\UnitService;

$openingBalance = (int) $statement['opening_balance'];
$totalDebit = (int) $statement['total_debit'];
$totalCredit = (int) $statement['total_credit'];
$closingBalance = (int) $statement['closing_balance'];
$kindLabels = ['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'];
$partyId = (int) $party['id'];
$partyName = (string) $party['name'];
$partyPhone = (string) ($party['phone'] ?? '');
$partyKind = $kindLabels[$party['kind']] ?? 'غير محدد';
$partyStatus = (int) $party['is_active'] === 1 ? 'نشط' : 'موقوف';
$closingDirection = $closingBalance > 0 ? 'عليه' : ($closingBalance < 0 ? 'له' : 'متزن');
$closingDescription = $closingBalance > 0
    ? 'مستحق على الطرف لصالح المحل (عليه)'
    : ($closingBalance < 0 ? 'مستحق للطرف على المحل (له)' : 'الحساب متزن');
$statementUrl = Url::to('/statement/' . $partyId . '?from=' . rawurlencode($from) . '&to=' . rawurlencode($to));
$arabicDate = static function (string $date): string {
    $formatted = (new DateTimeImmutable($date))->format('d/m/Y');
    return strtr($formatted, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
};
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>كشف حساب <?= View::e($partyName) ?> - <?= View::e($traderName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/print.css?v=6')) ?>">
</head>
<body class="print-page">
  <header class="print-toolbar no-print">
    <div class="print-toolbar-context">
      <a class="print-back-link" href="<?= View::e($statementUrl) ?>">
        <span aria-hidden="true">←</span>
        <span>العودة إلى كشف الحساب</span>
      </a>
      <span class="print-toolbar-divider" aria-hidden="true">|</span>
      <span class="print-toolbar-label"><i aria-hidden="true"></i> معاينة الطباعة الرسمية (ورقة A4 عمودي)</span>
    </div>
    <div class="print-toolbar-actions">
      <span class="print-toolbar-hint">مجهّز للطابعات الورقية وتصدير PDF</span>
      <button type="button" class="print-button" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7zM17 11h.01"/></svg>
        <span>طباعة كشف الحساب</span>
      </button>
    </div>
  </header>

  <main class="print-sheet" id="statement-print-document">
    <div class="print-sheet-content">
      <header class="print-letterhead">
        <div class="print-brand">
          <span class="print-brand-mark" aria-hidden="true">د</span>
          <div>
            <span class="print-trader"><?= View::e($traderName) ?></span>
            <p class="print-business-description">سجل الحسابات والمعاملات التجارية للمحلات</p>
          </div>
        </div>
        <div class="print-document-meta">
          <h1>كشف حساب</h1>
          <span>تاريخ الإصدار: <strong><bdi dir="ltr"><?= View::e($arabicDate(date('Y-m-d'))) ?></bdi></strong></span>
          <span>الفترة المالية: <strong><bdi dir="ltr"><?= View::e($arabicDate($from)) ?> — <?= View::e($arabicDate($to)) ?></bdi></strong></span>
        </div>
      </header>

      <section class="print-party-card keep-together" aria-label="بيانات الطرف">
        <div>
          <span class="print-label">اسم الطرف:</span>
          <strong class="print-party-name"><?= View::e($partyName) ?></strong>
        </div>
        <div>
          <span class="print-label">نوع الطرف:</span>
          <span class="print-party-kind"><?= View::e($partyKind) ?></span>
        </div>
        <div>
          <span class="print-label">رقم الهاتف:</span>
          <?php if ($partyPhone !== ''): ?>
            <bdi class="print-party-phone" dir="ltr"><?= View::e($partyPhone) ?></bdi>
          <?php else: ?>
            <span class="print-party-value">—</span>
          <?php endif; ?>
        </div>
        <div>
          <span class="print-label">حالة الحساب:</span>
          <span class="print-party-status <?= (int) $party['is_active'] === 1 ? 'is-active' : 'is-inactive' ?>">
            <i aria-hidden="true"></i><?= View::e($partyStatus) ?>
          </span>
        </div>
      </section>

      <section class="print-financial-summary keep-together" aria-label="ملخص الحساب">
        <div class="print-summary-item">
          <span>الرصيد الافتتاحي</span>
          <strong class="<?= Formatter::balanceClass($openingBalance) ?>">
            <?= View::e(Formatter::money(abs($openingBalance))) ?> <small>جنيه</small>
          </strong>
          <small class="print-summary-direction"><?= View::e($openingBalance > 0 ? 'عليه' : ($openingBalance < 0 ? 'له' : 'متزن')) ?></small>
        </div>
        <div class="print-summary-item">
          <span>إجمالي المسحوبات (مدين)</span>
          <strong class="text-ledger-red"><bdi dir="ltr">+<?= View::e(Formatter::money($totalDebit)) ?></bdi> <small>جنيه</small></strong>
          <small class="print-summary-direction">خلال الفترة</small>
        </div>
        <div class="print-summary-item">
          <span>إجمالي المدفوعات (دائن)</span>
          <strong class="text-ledger-green"><bdi dir="ltr">−<?= View::e(Formatter::money($totalCredit)) ?></bdi> <small>جنيه</small></strong>
          <small class="print-summary-direction">خلال الفترة</small>
        </div>
        <div class="print-summary-item print-summary-closing <?= Formatter::balanceClass($closingBalance) ?>">
          <span>الرصيد الختامي الحالي</span>
          <strong><?= View::e(Formatter::money(abs($closingBalance))) ?> <small>جنيه</small></strong>
          <small class="print-summary-direction"><?= View::e($closingDirection) ?></small>
        </div>
      </section>

      <section class="print-transactions" aria-label="سجل الحركات">
        <table class="print-table">
          <thead>
            <tr>
              <th scope="col" class="print-col-date">التاريخ</th>
              <th scope="col" class="print-col-reference">رقم المرجع</th>
              <th scope="col" class="print-col-description">البيان وتفاصيل الحركة</th>
              <th scope="col" class="print-col-amount">مدين (سحب)</th>
              <th scope="col" class="print-col-amount">دائن (دفعة)</th>
              <th scope="col" class="print-col-balance">الرصيد بعد الحركة</th>
            </tr>
          </thead>
          <tbody>
            <tr class="print-opening-row">
              <td>—</td>
              <td>—</td>
              <td>
                <strong>رصيد أول الفترة</strong>
                <small>الرصيد السابق لبداية الفترة المحددة</small>
              </td>
              <td class="print-numeric">—</td>
              <td class="print-numeric">—</td>
              <td class="print-balance-cell <?= Formatter::balanceClass($openingBalance) ?>">
                <?= View::e(Formatter::money(abs($openingBalance))) ?>
                <small><?= View::e($openingBalance > 0 ? 'عليه' : ($openingBalance < 0 ? 'له' : 'متزن')) ?></small>
              </td>
            </tr>
            <?php if ($statement['rows'] === []): ?>
              <tr class="print-empty-row"><td colspan="6">لا توجد حركات خلال هذه الفترة.</td></tr>
            <?php endif; ?>
            <?php foreach ($statement['rows'] as $row): ?>
              <?php
                $void = (int) $row['is_void'] === 1;
                $balance = (int) $row['running_balance'];
                $direction = (string) $row['direction'];
              ?>
              <tr class="<?= $void ? 'is-void' : '' ?>">
                <td class="print-nowrap"><bdi dir="ltr"><?= View::e($arabicDate((string) $row['tx_date'])) ?></bdi></td>
                <td class="print-reference"><bdi dir="ltr">#<?= (int) $row['id'] ?></bdi></td>
                <td class="print-description">
                  <strong><?= View::e(Formatter::type((string) $row['type'])) ?></strong>
                  <?php if ($row['quantity'] !== null): ?>
                    <small><?= View::e(UnitService::movementDescription($row)) ?></small>
                  <?php endif; ?>
                  <?php if ((string) ($row['note'] ?? '') !== ''): ?><small><?= View::e($row['note']) ?></small><?php endif; ?>
                  <?php if ($void): ?>
                    <small class="print-void-label">حركة ملغاة<?= !empty($row['void_reason']) ? ': ' . View::e($row['void_reason']) : '' ?></small>
                  <?php endif; ?>
                </td>
                <td class="print-numeric <?= !$void && $direction === 'debit' ? 'text-ledger-red' : '' ?>">
                  <?= !$void && $direction === 'debit' ? View::e(Formatter::money($row['amount'])) : '—' ?>
                </td>
                <td class="print-numeric <?= !$void && $direction === 'credit' ? 'text-ledger-green' : '' ?>">
                  <?= !$void && $direction === 'credit' ? View::e(Formatter::money($row['amount'])) : '—' ?>
                </td>
                <td class="print-balance-cell <?= Formatter::balanceClass($balance) ?>">
                  <?= View::e(Formatter::money(abs($balance))) ?>
                  <small><?= View::e($balance > 0 ? 'عليه' : ($balance < 0 ? 'له' : 'متزن')) ?></small>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="print-final-balance keep-together">
        <div>
          <span>الرصيد النهائي المستحق حتى نهاية الفترة:</span>
          <strong><?= View::e($closingDescription) ?></strong>
        </div>
        <div class="print-final-amount <?= Formatter::balanceClass($closingBalance) ?>">
          <strong><?= View::e(Formatter::money(abs($closingBalance))) ?> <small>جنيه</small></strong>
          <span><?= View::e($closingDirection) ?></span>
        </div>
      </section>
    </div>

    <footer class="print-signatures keep-together">
      <div class="print-signature-grid">
        <div><span>توقيع المحاسب / مسؤول الدفتر</span></div>
        <div><span>ختم المحل التجاري</span></div>
        <div><span>توقيع العميل بالعلم والمصادقة</span></div>
      </div>
      <div class="print-generated-footer">
        <span>كشف حساب صادر آليًا من نظام دفتر الحساب</span>
        <span>طبع في: <bdi dir="ltr"><?= View::e($arabicDate(date('Y-m-d'))) ?></bdi></span>
      </div>
    </footer>
  </main>
</body>
</html>
