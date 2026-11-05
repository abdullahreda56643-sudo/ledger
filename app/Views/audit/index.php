<?php
declare(strict_types=1);

use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$actionLabels = [
    'login' => 'تسجيل دخول',
    'logout' => 'تسجيل خروج',
    'login_failed' => 'محاولة دخول غير ناجحة',
    'user_created' => 'إنشاء حساب عامل',
    'user_activated' => 'تفعيل حساب مستخدم',
    'user_deactivated' => 'إيقاف حساب مستخدم',
    'party_created' => 'إضافة طرف جديد',
    'party_updated' => 'تعديل بيانات طرف',
    'party_activated' => 'إعادة تفعيل طرف',
    'party_deactivated' => 'إيقاف طرف',
    'transaction_created' => 'تسجيل حركة مالية',
    'opening_balance_created' => 'إضافة رصيد افتتاحي',
    'transaction_adjusted' => 'تسوية رصيد',
    'transaction_voided' => 'إلغاء حركة مالية',
];
$tableLabels = ['users' => 'المستخدمون', 'parties' => 'الأطراف', 'transactions' => 'الحركات المالية'];
$fieldLabels = [
    'username' => 'اسم الدخول',
    'name' => 'اسم الطرف',
    'phone' => 'رقم الهاتف',
    'kind' => 'نوع الطرف',
    'type' => 'نوع الحركة',
    'amount' => 'المبلغ',
    'direction' => 'اتجاه الرصيد',
    'party_id' => 'رقم الطرف',
    'group_id' => 'رقم مجموعة الحركة',
    'note' => 'ملاحظة',
    'reason' => 'سبب الإلغاء',
    'role' => 'الصلاحية',
];
$kindLabels = ['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'];
$roleLabels = ['owner' => 'مالك النظام', 'worker' => 'عامل'];
$entryCount = count($entries);
$actionCounts = [];
foreach ($entries as $entry) {
    $actionCounts[$entry['action']] = ($actionCounts[$entry['action']] ?? 0) + 1;
}
$topAction = $actionCounts === [] ? null : array_search(max($actionCounts), $actionCounts, true);
$formatDetail = static function (string $key, mixed $value) use ($fieldLabels, $kindLabels, $roleLabels): string {
    if ($key === 'amount' && is_numeric($value)) {
        return Formatter::money((int) $value) . ' جنيه';
    }
    if ($key === 'type' && is_string($value)) {
        return Formatter::type($value);
    }
    if ($key === 'direction' && is_string($value)) {
        return ['debit' => 'عليه (مدين)', 'credit' => 'له (دائن)'][$value] ?? $value;
    }
    if ($key === 'kind' && is_string($value)) {
        return $kindLabels[$value] ?? $value;
    }
    if ($key === 'role' && is_string($value)) {
        return $roleLabels[$value] ?? $value;
    }
    if (is_bool($value)) {
        return $value ? 'نعم' : 'لا';
    }
    if (is_array($value)) {
        return 'تفاصيل إضافية';
    }
    return (string) $value;
};
?>
<section class="audit-page" aria-label="سجل أنشطة النظام">
  <div class="audit-intro">
    <div>
      <span class="audit-eyebrow">متابعة النظام</span>
      <p>راجع التغييرات والأنشطة التي نفذها المستخدمون.</p>
    </div>
    <span class="audit-limit">آخر <?= number_format($entryCount) ?> من 500 نشاط</span>
  </div>

  <div class="audit-overview" aria-label="ملخص سجل التدقيق">
    <article class="audit-stat">
      <span class="audit-stat-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h16v13H4zM8 9h8M8 13h5"/></svg>
      </span>
      <span><small>الأنشطة المعروضة</small><strong><?= number_format($entryCount) ?></strong></span>
    </article>
    <article class="audit-stat">
      <span class="audit-stat-icon audit-stat-people" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM16 5.2a3.25 3.25 0 0 1 0 6.3"/></svg>
      </span>
      <span><small>مستخدمون نفذوا أنشطة</small><strong><?= number_format(count(array_unique(array_filter(array_column($entries, 'user_name'))))) ?></strong></span>
    </article>
    <article class="audit-stat">
      <span class="audit-stat-icon audit-stat-latest" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg>
      </span>
      <span><small>أكثر نشاط تكرارًا</small><strong class="audit-stat-text"><?= $topAction !== null ? View::e($actionLabels[$topAction] ?? $topAction) : 'لا يوجد' ?></strong></span>
    </article>
  </div>

  <section class="audit-panel" aria-labelledby="audit-list-title">
    <header class="audit-panel-heading">
      <div>
        <h2 id="audit-list-title">تفاصيل الأنشطة</h2>
        <p>الأحدث أولًا، مع توضيح منفذ النشاط والبيانات المرتبطة به.</p>
      </div>
    </header>

    <?php if ($entries === []): ?>
      <div class="audit-empty">
        <span aria-hidden="true">—</span>
        <h3>لا توجد أنشطة مسجلة</h3>
        <p>ستظهر هنا الأنشطة عند استخدام النظام.</p>
      </div>
    <?php else: ?>
      <ol class="audit-list">
        <?php foreach ($entries as $entry): ?>
          <?php
          $action = $entry['action'];
          $details = $entry['details_data'];
          $occurredAt = new DateTimeImmutable($entry['created_at']);
          $tableLabel = $tableLabels[$entry['table_name'] ?? ''] ?? 'سجل النظام';
          ?>
          <li class="audit-entry">
            <span class="audit-entry-mark" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h4l2.5-6 3.5 12 2.5-6H20"/></svg>
            </span>
            <article class="audit-entry-card">
              <header class="audit-entry-heading">
                <div class="audit-entry-title">
                  <h3><?= View::e($actionLabels[$action] ?? 'نشاط آخر') ?></h3>
                  <span class="audit-category"><?= View::e($tableLabel) ?></span>
                </div>
                <time datetime="<?= View::e($occurredAt->format(DATE_ATOM)) ?>">
                  <span><?= View::e($occurredAt->format('Y/m/d')) ?></span>
                  <span><?= View::e($occurredAt->format('H:i')) ?></span>
                </time>
              </header>

              <div class="audit-actor">
                <span class="audit-actor-avatar" aria-hidden="true"><?= View::e(mb_substr($entry['user_name'] ?? '؟', 0, 1)) ?></span>
                <span><small>نفذ النشاط</small><strong><?= View::e($entry['user_name'] ?? 'مستخدم غير معروف') ?></strong></span>
                <?php if ($entry['record_id'] !== null): ?>
                  <span class="audit-record">رقم السجل: <?= number_format((int) $entry['record_id']) ?></span>
                <?php endif; ?>
              </div>

              <?php if ($details !== [] || $entry['details_invalid'] || !empty($entry['ip'])): ?>
                <details class="audit-details">
                  <summary>عرض تفاصيل النشاط</summary>
                  <div class="audit-detail-content">
                    <?php if ($entry['details_invalid']): ?>
                      <p class="audit-detail-warning">تعذر تفسير تفاصيل هذا النشاط، وعُرضت البيانات الأصلية كما سُجلت.</p>
                      <pre><?= View::e($entry['details']) ?></pre>
                    <?php else: ?>
                      <?php foreach ($details as $key => $value): ?>
                        <div class="audit-detail">
                          <span><?= View::e($fieldLabels[$key] ?? 'تفصيل') ?></span>
                          <strong><?= View::e($formatDetail((string) $key, $value)) ?></strong>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($entry['ip'])): ?>
                      <div class="audit-detail audit-ip"><span>عنوان الاتصال</span><bdi><?= View::e($entry['ip']) ?></bdi></div>
                    <?php endif; ?>
                  </div>
                </details>
              <?php else: ?>
                <p class="audit-no-details">لا توجد تفاصيل إضافية لهذا النشاط.</p>
              <?php endif; ?>
            </article>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</section>
