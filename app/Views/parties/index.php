<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$isOwner = (Auth::user()['role'] ?? '') === 'owner';
$query = ['filter' => $filter, 'q' => $search];
if ($includeInactive) {
    $query['status'] = 'inactive';
}
$pageUrl = static function (int $targetPage) use ($query): string {
    return Url::to('/parties?' . http_build_query($query + ['page' => $targetPage]));
};
$filterUrl = static function (string $targetFilter) use ($query): string {
    return Url::to('/parties?' . http_build_query(array_merge($query, ['filter' => $targetFilter])));
};
$statusUrl = Url::to('/parties?' . http_build_query(array_merge(
    $query,
    $includeInactive ? ['status' => null] : ['status' => 'inactive']
)));
$startRecord = $totalParties > 0 ? (($page - 1) * $perPage) + 1 : 0;
$endRecord = min($page * $perPage, $totalParties);
$filters = [
    'all' => 'كل الأطراف',
    'customers' => 'عملاء',
    'suppliers' => 'موردون',
    'debtors' => 'عليهم رصيد',
];
$kindLabels = ['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'];
$today = date('Y-m-d');
?>
<section class="parties-page" aria-label="إدارة الأطراف">
  <div class="parties-intro">
    <a class="party-primary-action" href="<?= View::e(Url::to('/parties/create')) ?>" aria-label="إضافة طرف جديد">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
      <span>إضافة طرف جديد</span>
    </a>
  </div>

  <section class="party-tools" aria-label="البحث والتصفية">
    <div class="party-tools-top">
      <form class="party-search" method="get" action="<?= View::e(Url::to('/parties')) ?>">
        <input type="hidden" name="filter" value="<?= View::e($filter) ?>">
        <?php if ($includeInactive): ?><input type="hidden" name="status" value="inactive"><?php endif; ?>
        <label class="sr-only" for="party-query">ابحث باسم الطرف أو رقم الهاتف</label>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input id="party-query" name="q" value="<?= View::e($search) ?>" placeholder="ابحث بالاسم أو رقم الهاتف" autocomplete="off">
        <?php if ($search !== ''): ?>
          <a class="search-clear" href="<?= View::e(Url::to('/parties?' . http_build_query(array_merge($query, ['q' => null])))) ?>" aria-label="مسح البحث" title="مسح البحث">×</a>
        <?php endif; ?>
        <button type="submit">بحث</button>
      </form>

      <div class="party-filter-row">
        <div class="party-filters" role="group" aria-label="تصفية حسب نوع الطرف">
          <?php foreach ($filters as $key => $label): ?>
            <a class="party-filter <?= $filter === $key ? 'is-active' : '' ?>" href="<?= View::e($filterUrl($key)) ?>" <?= $filter === $key ? 'aria-current="page"' : '' ?>><?= View::e($label) ?></a>
          <?php endforeach; ?>
        </div>
        <?php if ($isOwner): ?>
          <a class="inactive-toggle <?= $includeInactive ? 'is-active' : '' ?>" href="<?= View::e($statusUrl) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12s3.25-6 9-6 9 6 9 6-3.25 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
            <span><?= $includeInactive ? 'عرض النشطة' : 'الأطراف الموقوفة' ?></span>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="party-result-summary" aria-live="polite">
      <span>النتائج المعروضة: <strong><?= number_format($totalParties) ?> طرف</strong></span>
      <span><?= $totalParties === 0 ? 'لا توجد نتائج' : 'عرض ' . number_format($startRecord) . '–' . number_format($endRecord) . ' من ' . number_format($totalParties) ?></span>
    </div>
  </section>

  <?php if ($parties === []): ?>
    <section class="parties-empty" aria-live="polite">
      <span class="empty-party-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM16 5.2a3.25 3.25 0 0 1 0 6.3M17 14h.75a3.5 3.5 0 0 1 3.5 3.5V19"/></svg>
      </span>
      <h2><?= $search !== '' ? 'لم نعثر على نتائج' : 'لا توجد أطراف هنا بعد' ?></h2>
      <p><?= $search !== '' ? 'جرّب البحث باسم مختلف أو رقم هاتف آخر.' : 'ابدأ بإضافة طرف جديد لتسجيل التعاملات ومتابعة رصيده.' ?></p>
      <?php if ($search !== ''): ?>
        <a class="party-secondary-action" href="<?= View::e(Url::to('/parties?' . http_build_query(array_merge($query, ['q' => null])))) ?>">مسح البحث</a>
      <?php else: ?>
        <a class="party-primary-action" href="<?= View::e(Url::to('/parties/create')) ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
          <span>إضافة طرف جديد</span>
        </a>
      <?php endif; ?>
    </section>
  <?php else: ?>
    <section class="party-table-shell" aria-label="قائمة الأطراف">
      <div class="party-table-scroll">
        <table class="party-table">
          <thead>
            <tr>
              <th scope="col">اسم الطرف</th>
              <th scope="col">النوع</th>
              <th scope="col">رقم الهاتف</th>
              <th scope="col">الرصيد الحالي</th>
              <th scope="col">الحالة</th>
              <th scope="col">إجراءات</th>
            </tr>
          </thead>
          <tbody>
      <?php foreach ($parties as $party): ?>
        <?php
        $balance = (int) $party['balance'];
        $partyId = (int) $party['id'];
        $statementUrl = Url::to('/statement/' . $partyId);
        $kind = $party['kind'];
        $kindIcon = ['customer' => 'person', 'supplier' => 'local_shipping', 'both' => 'swap_horiz'][$kind] ?? 'person';
        $balanceClass = $balance > 0 ? 'is-debit' : ($balance < 0 ? 'is-credit' : 'is-balanced');
        ?>
        <tr class="party-row <?= $includeInactive ? 'is-inactive' : '' ?>">
          <td class="party-name-cell" data-label="اسم الطرف">
            <div class="party-identity">
              <span class="party-avatar" aria-hidden="true"><?= View::e(mb_substr($party['name'], 0, 1)) ?></span>
              <div class="party-details">
                <div class="party-name-row">
                  <h2><a href="<?= View::e($statementUrl) ?>"><?= View::e($party['name']) ?></a></h2>
                  <span class="party-kind party-kind-<?= View::e($kind) ?>">
                    <span class="material-symbols-outlined" aria-hidden="true"><?= View::e($kindIcon) ?></span>
                    <?= View::e($kindLabels[$kind] ?? '') ?>
                  </span>
                </div>
              </div>
            </div>
          </td>
          <td class="party-kind-cell" data-label="النوع">
            <span class="party-kind party-kind-<?= View::e($kind) ?>">
              <span class="material-symbols-outlined" aria-hidden="true"><?= View::e($kindIcon) ?></span>
              <?= View::e($kindLabels[$kind] ?? '') ?>
            </span>
          </td>
          <td class="party-phone-cell" data-label="رقم الهاتف">
            <?php if ($party['phone'] !== ''): ?>
              <a class="party-phone" href="<?= View::e('tel:' . $party['phone']) ?>" dir="ltr">
                <span class="material-symbols-outlined" aria-hidden="true">call</span>
                <span><?= View::e($party['phone']) ?></span>
              </a>
            <?php else: ?>
              <span class="party-phone party-phone-empty">لا يوجد رقم هاتف</span>
            <?php endif; ?>
          </td>
          <td class="party-balance-cell" data-label="الرصيد الحالي">
            <div class="party-balance <?= $balanceClass ?>">
              <strong><?= View::e(Formatter::money(abs($balance))) ?></strong>
              <small>جنيه</small>
              <span class="party-balance-status">
                <?php if ($balance > 0): ?>
                  <span class="material-symbols-outlined" aria-hidden="true">arrow_upward</span>عليه
                <?php elseif ($balance < 0): ?>
                  <span class="material-symbols-outlined" aria-hidden="true">arrow_downward</span>له
                <?php else: ?>
                  متزن
                <?php endif; ?>
              </span>
            </div>
          </td>
          <td class="party-status-cell" data-label="الحالة">
            <span class="party-status <?= $includeInactive ? 'is-inactive' : 'is-active' ?>">
              <span aria-hidden="true"></span><?= $includeInactive ? 'موقوف' : 'نشط' ?>
            </span>
          </td>
          <td class="party-actions-cell" data-label="إجراءات">
            <div class="party-table-actions">
              <a class="party-action party-action-primary party-statement-link" href="<?= View::e($statementUrl) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/><path d="M14 4v5h4m-8 4h4m-4 3h4"/></svg>
                <span>كشف الحساب</span>
              </a>
            <?php if ($isOwner): ?>
              <details class="party-more">
                <summary class="party-action party-more-trigger" aria-label="خيارات <?= View::e($party['name']) ?>">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                  <span>خيارات</span>
                </summary>
                <div class="party-menu">
                  <a class="party-menu-link" href="<?= View::e($statementUrl) ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                    <span>كشف الحساب</span>
                  </a>
                  <a class="party-menu-link" href="<?= View::e(Url::to('/parties/' . $partyId . '/edit')) ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                    <span>تعديل البيانات</span>
                  </a>
              <?php if (!$includeInactive): ?>
                    <details class="party-submenu">
                      <summary>إضافة رصيد افتتاحي</summary>
                      <form action="<?= View::e(Url::to('/parties/' . $partyId . '/opening')) ?>" method="post" class="party-action-form">
                        <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                        <label>المبلغ بالجنيه<input required name="amount" type="number" min="0.01" step="0.01" inputmode="decimal"></label>
                        <label>تاريخ الرصيد<input required name="tx_date" type="date" max="<?= View::e($today) ?>" value="<?= View::e($today) ?>"></label>
                        <label>ملاحظة (اختياري)<input name="note" maxlength="255"></label>
                        <label>اتجاه الرصيد<span class="party-select-wrap"><select name="direction"><option value="debit">عليه (مدين)</option><option value="credit">له (دائن)</option></select></span></label>
                        <button type="submit">إضافة الرصيد</button>
                      </form>
                    </details>
                    <details class="party-submenu">
                      <summary>تسوية رصيد</summary>
                      <form action="<?= View::e(Url::to('/parties/' . $partyId . '/adjust')) ?>" method="post" class="party-action-form">
                        <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                        <label>المبلغ بالجنيه<input required name="amount" type="number" min="0.01" step="0.01" inputmode="decimal"></label>
                        <label>اتجاه التسوية<span class="party-select-wrap"><select name="direction"><option value="debit">عليه (مدين)</option><option value="credit">له (دائن)</option></select></span></label>
                        <label>سبب التسوية<input required name="note" maxlength="255"></label>
                        <button type="submit">حفظ التسوية</button>
                      </form>
                    </details>
                    <form action="<?= View::e(Url::to('/parties/' . $partyId . '/active')) ?>" method="post" data-confirm="هل تريد إيقاف هذا الطرف؟">
                      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                      <input type="hidden" name="active" value="0">
                      <button class="party-menu-danger" type="submit">إيقاف الطرف</button>
                    </form>
              <?php else: ?>
                <form action="<?= View::e(Url::to('/parties/' . $partyId . '/active')) ?>" method="post">
                  <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                  <input type="hidden" name="active" value="1">
                  <button class="party-menu-enable" type="submit">إعادة التفعيل</button>
                </form>
              <?php endif; ?>
                </div>
              </details>
            <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <?php if ($totalPages > 1): ?>
      <?php
      $firstVisiblePage = max(1, $page - 2);
      $lastVisiblePage = min($totalPages, $page + 2);
      ?>
      <nav class="party-pagination" aria-label="صفحات قائمة الأطراف">
        <a class="pagination-control <?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= View::e($pageUrl(max(1, $page - 1))) ?>" <?= $page === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg><span>السابق</span>
        </a>
        <div class="pagination-pages">
          <?php if ($firstVisiblePage > 1): ?>
            <a class="pagination-page" href="<?= View::e($pageUrl(1)) ?>">1</a>
            <?php if ($firstVisiblePage > 2): ?><span class="pagination-ellipsis" aria-hidden="true">…</span><?php endif; ?>
          <?php endif; ?>
          <?php for ($pageNumber = $firstVisiblePage; $pageNumber <= $lastVisiblePage; $pageNumber++): ?>
            <a class="pagination-page <?= $pageNumber === $page ? 'is-current' : '' ?>" href="<?= View::e($pageUrl($pageNumber)) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
          <?php endfor; ?>
          <?php if ($lastVisiblePage < $totalPages): ?>
            <?php if ($lastVisiblePage < $totalPages - 1): ?><span class="pagination-ellipsis" aria-hidden="true">…</span><?php endif; ?>
            <a class="pagination-page" href="<?= View::e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a>
          <?php endif; ?>
        </div>
        <a class="pagination-control <?= $page === $totalPages ? 'is-disabled' : '' ?>" href="<?= View::e($pageUrl(min($totalPages, $page + 1))) ?>" <?= $page === $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
          <span>التالي</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m10 6 6 6-6 6"/></svg>
        </a>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
