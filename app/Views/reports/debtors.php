<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$owed = (int) ($summary['owed_to_us'] ?? 0);
$weOwe = (int) ($summary['we_owe'] ?? 0);
$debtorCount = (int) ($summary['debtors_count'] ?? 0);
$kindLabels = ['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'];
?>
<section class="debt-report" aria-label="تقرير الأرصدة">
  <header class="report-intro">
    <div class="report-intro-copy">
      <span class="report-eyebrow">نظرة مالية</span>
      <h1 class="report-title"><?= View::e($pageTitle ?? 'تقرير المديونيات') ?></h1>
      <p>
        <span class="report-desktop-only">متابعة دقيقة للأرصدة المستحقة وتوزيع الديون على العملاء والموردين.</span>
        <span class="report-mobile-only">متابعة الأرصدة المستحقة وتوزيع الديون</span>
      </p>
    </div>
    <a class="report-parties-link" href="<?= View::e(Url::to('/parties?filter=debtors')) ?>" aria-label="قائمة الأطراف المدينة">
      <span>قائمة الأطراف المدينة</span>
      <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
    </a>
  </header>

  <section class="report-summary" aria-label="ملخص المديونيات">
    <article class="report-summary-card report-summary-debit">
      <div class="report-summary-head">
        <span>
          <span class="report-desktop-only">إجمالي المستحق لنا</span>
          <span class="report-mobile-only">المستحق لنا</span>
        </span>
        <span class="report-summary-icon" aria-hidden="true">
          <span class="material-symbols-outlined">arrow_upward</span>
        </span>
      </div>
      <div>
        <div class="report-summary-value">
          <strong><?= View::e(Formatter::money($owed)) ?></strong>
          <span>جنيه مصري</span>
        </div>
        <p class="report-summary-note">أموال مستحقة للمحل على الأطراف المدينة</p>
      </div>
    </article>
    <article class="report-summary-card report-summary-credit">
      <div class="report-summary-head">
        <span>
          <span class="report-desktop-only">إجمالي المستحق علينا</span>
          <span class="report-mobile-only">المستحق علينا</span>
        </span>
        <span class="report-summary-icon" aria-hidden="true">
          <span class="material-symbols-outlined">arrow_downward</span>
        </span>
      </div>
      <div>
        <div class="report-summary-value">
          <strong><?= View::e(Formatter::money($weOwe)) ?></strong>
          <span>جنيه مصري</span>
        </div>
        <p class="report-summary-note">أموال مستحقة للأطراف على المحل</p>
      </div>
    </article>
    <article class="report-summary-card report-summary-count">
      <div class="report-summary-head">
        <span>الأطراف المدينة</span>
        <span class="report-summary-icon" aria-hidden="true">
          <span class="material-symbols-outlined">group</span>
        </span>
      </div>
      <div>
        <div class="report-summary-value">
          <strong><?= number_format($debtorCount) ?></strong>
          <span>
            <span class="report-desktop-only">أطراف عليها مديونية</span>
            <span class="report-mobile-only">أطراف عليها رصيد</span>
          </span>
        </div>
        <p class="report-summary-note">تشمل الأطراف النشطة والموقوفة التي عليها رصيد</p>
      </div>
    </article>
  </section>

  <section class="debtors-panel" aria-labelledby="debtors-title">
    <header class="debtors-heading">
      <div>
        <h2 id="debtors-title">الأطراف الأكثر مديونية</h2>
        <p>مرتبة من أعلى رصيد مستحق إلى الأقل.</p>
      </div>
      <?php if ($maxBalance > 0): ?>
        <span class="debtors-max">
          الحد الأقصى للمديونية الفردية:
          <strong><?= View::e(Formatter::money($maxBalance)) ?> جنيه</strong>
        </span>
      <?php endif; ?>
      <span class="debtors-mobile-sort">مرتبة تنازليًا</span>
    </header>

    <?php if ($parties === []): ?>
      <div class="report-empty">
        <span class="report-empty-icon" aria-hidden="true">
          <span class="material-symbols-outlined">check_circle</span>
        </span>
        <h3>لا توجد أرصدة مدينة حاليًا</h3>
        <p>جميع الأطراف المسجلة سددت مستحقاتها بالكامل أو ليس عليها مديونيات قائمة.</p>
        <a href="<?= View::e(Url::to('/parties')) ?>">
          <span>عرض جميع الأطراف</span>
          <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
        </a>
      </div>
    <?php else: ?>
      <ol class="debtor-list">
        <?php foreach ($parties as $index => $party): ?>
          <?php
          $balance = (int) $party['balance'];
          $width = $maxBalance > 0 ? max(2, round($balance / $maxBalance * 100, 1)) : 0;
          $share = $owed > 0 ? round($balance / $owed * 100, 1) : 0;
          ?>
          <li class="debtor-item">
            <div class="debtor-row">
              <div class="debtor-identity">
                <span class="debtor-rank" aria-label="الترتيب <?= $index + 1 ?>"><?= $index + 1 ?></span>
                <div class="debtor-details">
                  <div class="debtor-title-line">
                    <a class="debtor-name" href="<?= View::e(Url::to('/statement/' . (int) $party['id'])) ?>"><?= View::e($party['name']) ?></a>
                    <span class="debtor-kind"><?= View::e($kindLabels[$party['kind']] ?? '') ?></span>
                    <?php if ((int) $party['is_active'] !== 1): ?><span class="debtor-status">موقوف</span><?php endif; ?>
                  </div>
                  <?php if ($party['phone'] !== ''): ?>
                    <a class="debtor-phone" href="<?= View::e('tel:' . $party['phone']) ?>" dir="ltr">
                      <span class="material-symbols-outlined" aria-hidden="true">call</span>
                      <span><?= View::e($party['phone']) ?></span>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="debtor-balance">
                <div class="debtor-amount">
                  <strong><?= View::e(Formatter::money($balance)) ?></strong>
                  <small>جنيه</small>
                  <span class="debtor-balance-pill">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_upward</span>
                    عليه
                  </span>
                </div>
                <span class="debtor-share">يمثل <?= number_format($share, 1) ?>% من إجمالي الديون</span>
              </div>
            </div>
            <div class="debtor-progress" role="progressbar" aria-label="نسبة مديونية <?= View::e($party['name']) ?> مقارنة بأعلى رصيد" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $width ?>">
              <span style="width: <?= $width ?>%"></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</section>
