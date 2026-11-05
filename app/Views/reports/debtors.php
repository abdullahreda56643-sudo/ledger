<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$owed = (int) ($summary['owed_to_us'] ?? 0);
$weOwe = (int) ($summary['we_owe'] ?? 0);
$debtorCount = (int) ($summary['debtors_count'] ?? 0);
?>
<section class="debt-report" aria-label="تقرير الأرصدة">
  <div class="report-intro">
    <div>
      <span class="report-eyebrow">نظرة مالية</span>
      <p>ملخص الأرصدة المستحقة ومتابعة الأطراف الأكثر مديونية.</p>
    </div>
      <a class="report-parties-link" href="<?= View::e(Url::to('/parties?filter=debtors')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM16 5.2a3.25 3.25 0 0 1 0 6.3M17 14h.75a3.5 3.5 0 0 1 3.5 3.5V19"/></svg>
        <span>قائمة الأطراف المدينة</span>
      </a>
  </div>

  <div class="report-summary">
    <article class="report-summary-card report-summary-credit">
      <span class="report-summary-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7.5h16v12H4z"/><path d="M4 10h16M8 15h3"/><path d="m15 4 2 2 3-3"/></svg>
      </span>
      <div class="report-summary-copy">
        <span>إجمالي المستحق لنا</span>
        <strong><?= View::e(Formatter::money($owed)) ?> <small>جنيه</small></strong>
        <span class="report-summary-note">أرصدة مدينة لدى الأطراف</span>
      </div>
    </article>
    <article class="report-summary-card report-summary-debit">
      <span class="report-summary-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7.5h16v12H4z"/><path d="M4 10h16M8 15h3"/><path d="m15 4 2 2 3-3"/></svg>
      </span>
      <div class="report-summary-copy">
        <span>إجمالي المستحق علينا</span>
        <strong><?= View::e(Formatter::money($weOwe)) ?> <small>جنيه</small></strong>
        <span class="report-summary-note">أرصدة دائنة لدى الأطراف</span>
      </div>
    </article>
    <article class="report-summary-card report-summary-count">
      <span class="report-summary-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM17 8h5m-2.5-2.5v5"/></svg>
      </span>
      <div class="report-summary-copy">
        <span>الأطراف المدينة</span>
        <strong><?= number_format($debtorCount) ?> <small>طرف</small></strong>
        <span class="report-summary-note">لديها رصيد مستحق</span>
      </div>
    </article>
  </div>

  <section class="debtors-panel" aria-labelledby="debtors-title">
    <header class="debtors-heading">
      <div>
        <h2 id="debtors-title">الأطراف الأكثر مديونية</h2>
        <p>مرتبة من أعلى رصيد مستحق إلى الأقل.</p>
      </div>
      <?php if ($parties !== []): ?>
        <span class="debtors-count"><?= number_format(count($parties)) ?> طرف</span>
      <?php endif; ?>
    </header>

    <?php if ($parties === []): ?>
      <div class="report-empty">
        <span class="report-empty-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h16v13H4zM8 9h8M8 13h5"/></svg>
        </span>
        <h3>لا توجد أرصدة مدينة حاليًا</h3>
        <p>ستظهر هنا الأطراف التي لديها مبالغ مستحقة.</p>
        <a href="<?= View::e(Url::to('/parties')) ?>">عرض الأطراف</a>
      </div>
    <?php else: ?>
      <ol class="debtor-list">
        <?php foreach ($parties as $index => $party): ?>
          <?php
          $balance = (int) $party['balance'];
          $width = $maxBalance > 0 ? max(2, (int) round($balance / $maxBalance * 100)) : 0;
          ?>
          <li class="debtor-item">
            <span class="debtor-rank" aria-label="الترتيب <?= $index + 1 ?>"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div class="debtor-main">
              <div class="debtor-details">
                <a class="debtor-name" href="<?= View::e(Url::to('/statement/' . (int) $party['id'])) ?>"><?= View::e($party['name']) ?></a>
                <?php if ($party['phone'] !== ''): ?>
                  <a class="debtor-phone" href="<?= View::e('tel:' . $party['phone']) ?>"><?= View::e($party['phone']) ?></a>
                <?php endif; ?>
              </div>
              <strong class="debtor-balance">عليه <?= View::e(Formatter::money($balance)) ?> جنيه</strong>
            </div>
            <div class="debtor-progress" role="progressbar" aria-label="نسبة المديونية مقارنة بأعلى رصيد" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $width ?>">
              <span style="width: <?= $width ?>%"></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</section>
