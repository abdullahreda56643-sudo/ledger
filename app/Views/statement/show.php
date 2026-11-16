<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;
use App\Services\UnitService;

$openingBalance = (int) $statement['opening_balance'];
$closingBalance = (int) $statement['closing_balance'];
$totalDebit = (int) $statement['total_debit'];
$totalCredit = (int) $statement['total_credit'];
$rows = array_reverse($statement['rows']);
$kindLabels = ['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'];
$partyName = (string) $party['name'];
$partyInitial = preg_match('/^./u', $partyName, $initialMatch) === 1 ? $initialMatch[0] : '؟';
$partyPhone = (string) ($party['phone'] ?? '');
$registeredDate = substr((string) ($party['created_at'] ?? ''), 0, 10);
$closingDirection = $closingBalance > 0 ? 'عليه' : ($closingBalance < 0 ? 'له' : 'متزن');
$closingTone = $closingBalance > 0 ? 'is-debit' : ($closingBalance < 0 ? 'is-credit' : 'is-balanced');
$partyId = (int) $party['id'];
$printUrl = Url::to('/statement/' . $partyId . '/print?from=' . rawurlencode($from) . '&to=' . rawurlencode($to));
?>
<section class="statement-screen" aria-label="كشف الحساب">
  <div class="statement-back">
    <a href="<?= View::e(Url::to('/parties')) ?>">
      <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
      <span>العودة إلى قائمة الأطراف</span>
    </a>
  </div>

  <section class="statement-party-card" aria-labelledby="statement-party-title">
    <div class="statement-heading">
      <div class="statement-party">
        <span class="statement-party-mark" aria-hidden="true"><?= View::e($partyInitial) ?></span>
        <div class="statement-party-copy">
          <span class="statement-eyebrow">كشف الحساب <span aria-hidden="true">·</span> سجل الحركات المالية</span>
          <div class="statement-party-title-line">
            <h1 id="statement-party-title" class="statement-party-title"><?= View::e($partyName) ?></h1>
            <span class="statement-kind">
              <span class="material-symbols-outlined" aria-hidden="true">person</span>
              <?= View::e($kindLabels[$party['kind']] ?? '') ?>
            </span>
            <span class="statement-status <?= (int) $party['is_active'] === 1 ? 'is-active' : 'is-inactive' ?>">
              <span aria-hidden="true"></span>
              <?= (int) $party['is_active'] === 1 ? 'نشط' : 'موقوف' ?>
            </span>
          </div>
          <div class="statement-party-meta">
            <?php if ($partyPhone !== ''): ?>
              <a href="<?= View::e('tel:' . $partyPhone) ?>" dir="ltr">
                <span class="material-symbols-outlined" aria-hidden="true">call</span>
                <?= View::e($partyPhone) ?>
              </a>
            <?php endif; ?>
            <?php if ($registeredDate !== ''): ?>
              <span>تاريخ الإضافة: <time datetime="<?= View::e($registeredDate) ?>"><?= View::e($registeredDate) ?></time></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="statement-actions">
        <a target="_blank" rel="noopener noreferrer" href="<?= View::e($whatsappUrl) ?>" class="statement-action statement-action-primary">
          <span class="material-symbols-outlined" aria-hidden="true">chat</span>
          <span>إرسال واتساب</span>
        </a>
        <a target="_blank" rel="noopener noreferrer" href="<?= View::e($printUrl) ?>" class="statement-action">
          <span class="material-symbols-outlined" aria-hidden="true">print</span>
          <span>طباعة / PDF</span>
        </a>
      </div>
    </div>

    <form method="get" class="statement-filter statement-filter-desktop" aria-label="تحديد فترة كشف الحساب">
      <label class="statement-field">
        <span>من:</span>
        <input type="date" name="from" value="<?= View::e($from) ?>" required>
      </label>
      <label class="statement-field">
        <span>إلى:</span>
        <input type="date" name="to" value="<?= View::e($to) ?>" required>
      </label>
      <button type="submit" class="statement-action statement-filter-button">تحديث</button>
    </form>

    <details class="statement-filter-mobile">
      <summary>
        <span>الفترة: <b dir="ltr"><?= View::e($from) ?> — <?= View::e($to) ?></b></span>
        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
      </summary>
      <form method="get" class="statement-filter" aria-label="تحديد فترة كشف الحساب">
        <label class="statement-field">
          <span>من:</span>
          <input type="date" name="from" value="<?= View::e($from) ?>" required>
        </label>
        <label class="statement-field">
          <span>إلى:</span>
          <input type="date" name="to" value="<?= View::e($to) ?>" required>
        </label>
        <button type="submit" class="statement-action statement-filter-button">تحديث</button>
      </form>
    </details>
  </section>

  <section class="statement-balance-desktop" aria-label="ملخص أرصدة الحساب">
    <article class="statement-balance-item">
      <span>الرصيد الافتتاحي</span>
      <strong class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::money(abs($openingBalance))) ?> <small>جنيه</small></strong>
      <span class="statement-balance-note"><?= $openingBalance > 0 ? 'عليه' : ($openingBalance < 0 ? 'له' : 'متزن') ?> في بداية الفترة</span>
    </article>
    <article class="statement-balance-item statement-debit-total">
      <span>إجمالي الحركات المدينة</span>
      <strong class="text-ledger-red"><?= View::e(Formatter::money($totalDebit)) ?> <small>جنيه</small></strong>
      <span class="statement-balance-note">مجموع الحركات المدينة خلال الفترة</span>
    </article>
    <article class="statement-balance-item statement-credit-total">
      <span>إجمالي الحركات الدائنة</span>
      <strong class="text-ledger-green"><?= View::e(Formatter::money($totalCredit)) ?> <small>جنيه</small></strong>
      <span class="statement-balance-note">مجموع الحركات الدائنة خلال الفترة</span>
    </article>
    <article class="statement-balance-item statement-balance-current <?= $closingTone ?>">
      <span>الرصيد الحالي المستحق</span>
      <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::money(abs($closingBalance))) ?> <small>جنيه</small></strong>
      <span class="statement-balance-direction <?= Formatter::balanceClass($closingBalance) ?>"><?= View::e($closingDirection) ?></span>
    </article>
  </section>

  <section class="statement-balance-mobile" aria-label="ملخص أرصدة الحساب">
    <article class="statement-current-card <?= $closingTone ?>">
      <div>
        <span>الرصيد الحالي المستحق</span>
        <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::money(abs($closingBalance))) ?> <small>جنيه</small></strong>
      </div>
      <span class="statement-current-direction <?= Formatter::balanceClass($closingBalance) ?>">
        <span class="material-symbols-outlined" aria-hidden="true"><?= $closingBalance < 0 ? 'arrow_downward' : ($closingBalance > 0 ? 'arrow_upward' : 'horizontal_rule') ?></span>
        <?= View::e($closingDirection) ?>
      </span>
    </article>
    <div class="statement-mobile-metrics">
      <article class="statement-mobile-metric">
        <span>الافتتاحي</span>
        <strong class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::money(abs($openingBalance))) ?></strong>
        <small>جنيه</small>
      </article>
      <article class="statement-mobile-metric statement-debit-total">
        <span>حركات مدينة</span>
        <strong><?= View::e(Formatter::money($totalDebit)) ?></strong>
        <small>جنيه</small>
      </article>
      <article class="statement-mobile-metric statement-credit-total">
        <span>حركات دائنة</span>
        <strong><?= View::e(Formatter::money($totalCredit)) ?></strong>
        <small>جنيه</small>
      </article>
    </div>
  </section>

  <section class="statement-ledger" aria-labelledby="statement-ledger-title">
    <div class="statement-section-heading statement-ledger-heading">
      <div>
        <h2 id="statement-ledger-title">سجل الحركات</h2>
        <p>تفاصيل المسحوبات والدفعات والرصيد التراكمي مرتبة زمنيًا.</p>
      </div>
      <span class="statement-count">إجمالي الحركات: <?= number_format(count($rows)) ?></span>
    </div>

    <div class="statement-table-wrap">
      <table class="statement-table">
        <thead>
          <tr>
            <th scope="col" class="statement-date-column">التاريخ</th>
            <th scope="col" class="statement-reference-column">رقم المرجع</th>
            <th scope="col" class="statement-description-column">البيان / تفاصيل الحركة</th>
            <th scope="col" class="statement-amount-column">مدين</th>
            <th scope="col" class="statement-amount-column">دائن</th>
            <th scope="col" class="statement-balance-column">الرصيد بعد الحركة</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
              $void = (int) $row['is_void'] === 1;
              $balance = (int) $row['running_balance'];
              $isToday = $row['tx_date'] === date('Y-m-d') && !$void;
              $createdAt = new DateTimeImmutable((string) $row['created_at']);
              $timeLabel = $createdAt->format('g:i') . ($createdAt->format('a') === 'am' ? ' ص' : ' م');
              $rowDirection = (string) $row['direction'];
            ?>
            <tr class="<?= $void ? 'statement-void' : '' ?> <?= $row['type'] === 'opening' ? 'statement-opening' : '' ?> <?= $isToday ? 'statement-today' : '' ?>">
              <td class="statement-nowrap" data-label="التاريخ">
                <time datetime="<?= View::e($row['tx_date']) ?>"><?= View::e($row['tx_date']) ?></time>
                <small class="statement-time"><?= View::e($timeLabel) ?></small>
                <?php if ($isToday): ?><span class="statement-today-label">اليوم</span><?php endif; ?>
              </td>
              <td class="statement-nowrap" data-label="رقم المرجع"><span dir="ltr">#<?= (int) $row['id'] ?></span></td>
              <td class="statement-description" data-label="البيان / تفاصيل الحركة">
                <span class="statement-transaction-type"><?= View::e(Formatter::type($row['type'])) ?></span>
                <?php if ($row['quantity'] !== null): ?><small><?= View::e(UnitService::movementDescription($row)) ?></small><?php endif; ?>
                <?php if ((string) ($row['note'] ?? '') !== ''): ?><small><?= View::e($row['note']) ?></small><?php endif; ?>
                <?php if ($void): ?><small class="statement-void-label">حركة ملغاة<?= !empty($row['void_reason']) ? ': ' . View::e($row['void_reason']) : '' ?></small><?php endif; ?>
              </td>
              <td class="statement-nowrap text-ledger-red" data-label="مدين"><?= !$void && $rowDirection === 'debit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
              <td class="statement-nowrap text-ledger-green" data-label="دائن"><?= !$void && $rowDirection === 'credit' ? View::e(Formatter::money($row['amount'])) : '—' ?></td>
              <td class="statement-nowrap" data-label="الرصيد بعد الحركة">
                <span class="<?= Formatter::balanceClass($balance) ?>"><?= View::e(Formatter::money(abs($balance))) ?></span>
                <small class="statement-balance-suffix"><?= $balance > 0 ? 'عليه' : ($balance < 0 ? 'له' : 'متزن') ?></small>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr class="statement-opening">
            <td data-label="التاريخ">—</td>
            <td data-label="رقم المرجع">—</td>
            <td data-label="البيان / تفاصيل الحركة">رصيد أول الفترة</td>
            <td data-label="مدين">—</td>
            <td data-label="دائن">—</td>
            <td data-label="الرصيد بعد الحركة">
              <span class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::money(abs($openingBalance))) ?></span>
              <small class="statement-balance-suffix"><?= $openingBalance > 0 ? 'عليه' : ($openingBalance < 0 ? 'له' : 'متزن') ?></small>
            </td>
          </tr>
          <?php if ($rows === []): ?>
            <tr class="statement-empty"><td colspan="6">لا توجد حركات خلال هذه الفترة.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="statement-mobile-list">
      <?php if ($rows === []): ?>
        <div class="statement-mobile-empty">لا توجد حركات خلال هذه الفترة.</div>
      <?php endif; ?>
      <?php foreach ($rows as $row): ?>
        <?php
          $void = (int) $row['is_void'] === 1;
          $balance = (int) $row['running_balance'];
          $rowDirection = (string) $row['direction'];
          $createdAt = new DateTimeImmutable((string) $row['created_at']);
          $timeLabel = $createdAt->format('g:i') . ($createdAt->format('a') === 'am' ? ' ص' : ' م');
          $amountClass = $rowDirection === 'debit' ? 'text-ledger-red' : 'text-ledger-green';
          $directionLabel = $rowDirection === 'debit' ? 'مدين' : 'دائن';
        ?>
        <article class="statement-mobile-card <?= $void ? 'statement-void' : '' ?> <?= $row['type'] === 'opening' ? 'statement-opening' : '' ?>">
          <div class="statement-mobile-card-head">
            <div class="statement-mobile-description">
              <strong><?= View::e(Formatter::type($row['type'])) ?></strong>
              <span dir="ltr">#<?= (int) $row['id'] ?></span>
            </div>
            <div class="statement-mobile-amount <?= $void ? 'text-ledger-muted' : $amountClass ?>">
              <?php if ($void): ?>
                <span>—</span>
                <small>ملغاة</small>
              <?php else: ?>
                <strong><?= $rowDirection === 'debit' ? '+' : '-' ?><?= View::e(Formatter::money($row['amount'])) ?> <small>جنيه</small></strong>
                <span><?= $directionLabel ?> (<?= View::e(Formatter::type($row['type'])) ?>)</span>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($row['quantity'] !== null): ?>
            <p><?= View::e(UnitService::movementDescription($row)) ?></p>
          <?php endif; ?>
          <?php if ((string) ($row['note'] ?? '') !== ''): ?><p><?= View::e($row['note']) ?></p><?php endif; ?>
          <?php if ($void && !empty($row['void_reason'])): ?><p class="statement-void-label"><?= View::e($row['void_reason']) ?></p><?php endif; ?>
          <footer>
            <time datetime="<?= View::e($row['tx_date']) ?>"><?= View::e($row['tx_date']) ?> · <?= View::e($timeLabel) ?></time>
            <span>الرصيد بعد الحركة: <strong class="<?= Formatter::balanceClass($balance) ?>"><?= View::e(Formatter::money(abs($balance))) ?> <?= $balance > 0 ? 'عليه' : ($balance < 0 ? 'له' : 'متزن') ?></strong></span>
          </footer>
        </article>
      <?php endforeach; ?>
      <article class="statement-mobile-card statement-opening">
        <div class="statement-mobile-card-head">
          <div class="statement-mobile-description"><strong>رصيد أول الفترة</strong></div>
          <div class="statement-mobile-amount text-ledger-muted"><span>—</span></div>
        </div>
        <footer>
          <span>قبل بداية الفترة</span>
          <span>الرصيد: <strong class="<?= Formatter::balanceClass($openingBalance) ?>"><?= View::e(Formatter::money(abs($openingBalance))) ?> <?= $openingBalance > 0 ? 'عليه' : ($openingBalance < 0 ? 'له' : 'متزن') ?></strong></span>
        </footer>
      </article>
    </div>

    <footer class="statement-closing">
      <span>صافي الرصيد</span>
      <strong class="<?= Formatter::balanceClass($closingBalance) ?>"><?= View::e(Formatter::money(abs($closingBalance))) ?> <small>جنيه · <?= View::e($closingDirection) ?></small></strong>
    </footer>
  </section>
</section>
