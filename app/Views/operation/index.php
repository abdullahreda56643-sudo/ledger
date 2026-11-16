<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;
use App\Services\OperationPolicy;

$old = $old ?? [];
$type = $old['type'] ?? 'sell';
$type = in_array($type, ['sell', 'pay', 'supply', 'pay_supplier'], true) ? $type : 'sell';
$today = date('Y-m-d');
$isOwner = (Auth::user()['role'] ?? '') === 'owner';
$startRecord = $totalTransactions > 0 ? (($page - 1) * $perPage) + 1 : 0;
$endRecord = min($page * $perPage, $totalTransactions);
$historyPageUrl = static function (int $targetPage) use ($from, $to): string {
    return Url::to('/?' . http_build_query(['from' => $from, 'to' => $to, 'page' => $targetPage]));
};
$usesWeight = in_array($type, ['sell', 'supply'], true);
$selectedPartyName = '';
$selectedPartyId = (string) ($old['party_id'] ?? '');
foreach ($parties as $party) {
    if ((string) $party['id'] === $selectedPartyId) {
        $selectedPartyName = (string) $party['name'];
        break;
    }
}
$typeIcons = [
    'sell' => 'shopping_bag',
    'pay' => 'payments',
    'supply' => 'local_shipping',
    'pay_supplier' => 'account_balance_wallet',
];
?>
<div class="operation-screen" aria-label="التشغيل اليومي">
  <section class="operation-workspace" aria-labelledby="operation-form-title">
    <div class="workspace-heading">
      <h2 id="operation-form-title">تسجيل حركة جديدة</h2>
      <div class="selected-party-summary">
        <span>الطرف المحدد:</span>
        <strong id="top-party-pill"><?= $selectedPartyName !== '' ? View::e($selectedPartyName) : 'اختر طرفًا' ?></strong>
      </div>
    </div>

    <form id="workspace-form" action="<?= View::e(Url::to('/transactions')) ?>" method="post" data-operation-form class="operation-form">
      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">

      <fieldset class="operation-fieldset">
        <legend class="visually-hidden">نوع الحركة</legend>
        <div class="operation-types" role="radiogroup" aria-label="نوع الحركة">
          <?php foreach (['sell' => 'سحب', 'pay' => 'دفعة من الطرف', 'supply' => 'توريد', 'pay_supplier' => 'دفعة لمورد'] as $value => $label): ?>
            <label class="operation-type <?= $type === $value ? 'is-selected' : '' ?>">
              <input class="visually-hidden" type="radio" name="type" value="<?= View::e($value) ?>" <?= $type === $value ? 'checked' : '' ?> data-operation-type>
              <span class="material-symbols-outlined" aria-hidden="true"><?= View::e($typeIcons[$value]) ?></span>
              <span><?= View::e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="operation-form-grid">
        <div class="operation-fields">
          <div class="party-picker">
            <label for="party-search" class="field-label">الطرف (العميل أو المورد)</label>
            <div class="party-search-wrap">
              <span class="material-symbols-outlined" aria-hidden="true">search</span>
              <input id="party-search" type="search" value="<?= View::e($selectedPartyName) ?>" placeholder="ابحث بالاسم أو رقم الموبايل..." autocomplete="off" required aria-autocomplete="list" aria-controls="party-dropdown" aria-expanded="false">
              <div id="party-dropdown" class="party-dropdown hidden" role="listbox" aria-label="نتائج البحث عن الأطراف">
                <?php foreach ($parties as $party): ?>
                  <button type="button" class="party-option hidden" role="option" aria-selected="false" data-party-option data-party-id="<?= (int) $party['id'] ?>" data-search="<?= View::e($party['name'] . ' ' . $party['phone']) ?>" data-name="<?= View::e($party['name']) ?>">
                    <span><?= View::e($party['name']) ?></span>
                    <small><?= View::e($party['phone']) ?></small>
                  </button>
                <?php endforeach; ?>
                <p class="party-no-results hidden" data-party-empty>لا توجد نتائج مطابقة.</p>
              </div>
            </div>
            <select id="party-id" name="party_id" class="visually-hidden" tabindex="-1" aria-hidden="true">
              <option value="">اختر الطرف</option>
              <?php foreach ($parties as $party): ?>
                <option value="<?= (int) $party['id'] ?>" data-search="<?= View::e($party['name'] . ' ' . $party['phone']) ?>" data-operations="<?= View::e(implode(' ', OperationPolicy::operationsForPartyType((string) $party['kind']))) ?>" <?= $selectedPartyId === (string) $party['id'] ? 'selected' : '' ?>><?= View::e($party['name'] . ' — ' . $party['phone']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div data-weight-fields class="<?= $usesWeight ? '' : 'hidden' ?> weighted-inputs amount-fields">
            <label class="field-group">
              <span class="field-label">الوزن (كجم)</span>
              <input name="weight" type="number" step="0.001" min="0.001" max="1000" value="<?= View::e($old['weight'] ?? '') ?>" inputmode="decimal" data-weight placeholder="مثال: 45">
            </label>
            <label class="field-group">
              <span class="field-label">السعر للكيلو (جنيه)</span>
              <input name="unit_price" type="number" step="0.01" min="0.01" value="<?= View::e($old['unit_price'] ?? '') ?>" inputmode="decimal" data-price placeholder="أدخل سعر الكيلو">
            </label>
            <div class="estimated-total">
              <span class="field-label">الإجمالي التقديري</span>
              <strong><b data-total>0</b> <small>جنيه</small></strong>
            </div>
          </div>

          <div data-amount-fields class="<?= $usesWeight ? 'hidden' : '' ?> direct-input amount-fields">
            <label class="field-group">
              <span class="field-label">المبلغ (جنيه)</span>
              <input name="amount" type="number" step="0.01" min="0.01" value="<?= View::e($old['amount'] ?? '') ?>" inputmode="decimal" data-amount placeholder="أدخل مبلغ الحركة">
            </label>
          </div>

          <div class="operation-secondary-fields">
            <label data-pay-now-wrap class="<?= $type === 'sell' ? '' : 'hidden' ?> field-group">
              <span class="field-label">دفع الآن (اختياري)</span>
              <input name="pay_now" type="number" step="0.01" min="0.01" value="<?= View::e($old['pay_now'] ?? '') ?>" inputmode="decimal" data-pay-now placeholder="0">
            </label>

            <label class="field-group">
              <span class="field-label">تاريخ الحركة</span>
              <?php if ($isOwner): ?>
                <input name="tx_date" type="date" max="<?= View::e($today) ?>" value="<?= View::e($old['tx_date'] ?? $today) ?>">
              <?php else: ?>
                <input type="hidden" name="tx_date" value="<?= View::e($today) ?>">
                <input type="date" value="<?= View::e($today) ?>" disabled aria-describedby="staff-date-hint">
              <?php endif; ?>
              <?php if (!$isOwner): ?><span id="staff-date-hint" class="staff-date-hint">مقيد بتاريخ اليوم للموظفين</span><?php endif; ?>
            </label>

            <label class="field-group">
              <span class="field-label">ملاحظة (اختياري)</span>
              <input name="note" type="text" maxlength="255" value="<?= View::e($old['note'] ?? '') ?>" placeholder="فرز أول، تسليم المحل...">
            </label>
          </div>
        </div>

        <aside class="operation-summary" aria-label="أثر الحركة على الرصيد">
          <div class="balance-impact">
            <span class="summary-label">أثر الحركة على الرصيد</span>
            <div class="balance-row">
              <span>الرصيد الحالي</span>
              <span class="balance-number">
                <strong data-current-balance>اختر طرفًا</strong>
                <span data-current-direction class="balance-badge hidden"></span>
              </span>
            </div>
            <div class="balance-row balance-after-row">
              <span>بعد الحركة</span>
              <span class="balance-number">
                <strong data-next-balance>—</strong>
                <span data-next-direction class="balance-badge hidden"></span>
              </span>
            </div>
          </div>
          <div class="form-feedback hidden" data-form-feedback role="status"></div>
          <button type="submit" class="save-operation">
            <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
            <span>حفظ الحركة</span>
          </button>
        </aside>
      </div>
    </form>
  </section>

  <section class="daily-panel" aria-labelledby="ledger-heading">
    <div class="ledger-heading">
      <div class="ledger-title">
        <h2 id="ledger-heading">سجل الحركات</h2>
        <span class="ledger-count"><?= number_format($totalTransactions) ?> حركات</span>
      </div>
      <form class="history-filter" method="get" action="<?= View::e(Url::to('/')) ?>">
        <label>
          <span>من تاريخ</span>
          <input type="date" name="from" max="<?= View::e($today) ?>" value="<?= View::e($from) ?>" required>
        </label>
        <label>
          <span>إلى تاريخ</span>
          <input type="date" name="to" max="<?= View::e($today) ?>" value="<?= View::e($to) ?>" required>
        </label>
        <button type="submit">عرض الحركات</button>
      </form>
    </div>

    <?php if ($transactions === []): ?>
      <div class="empty-activity">
        <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
        <p>لسه مفيش حركات</p>
        <span>ابدأ بتسجيل أول حركة من مساحة العمل بالأعلى</span>
      </div>
    <?php else: ?>
      <div class="transaction-table-wrap">
        <table class="transaction-table">
          <thead>
            <tr>
              <th scope="col">الطرف</th>
              <th scope="col">النوع</th>
              <th scope="col">التفاصيل</th>
              <th scope="col">بواسطة / التاريخ</th>
              <th scope="col">ملاحظة</th>
              <th scope="col" class="text-left">المبلغ</th>
              <th scope="col" class="text-left">إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $transaction): ?>
              <?php
              $isVoid = (int) $transaction['is_void'] === 1;
              $isDebit = $transaction['direction'] === 'debit';
              ?>
              <tr class="<?= $isVoid ? 'is-void' : '' ?>">
                <td class="table-party" data-label="الطرف">
                  <a class="party-name" href="<?= View::e(Url::to('/statement/' . (int) $transaction['party_id'])) ?>"><?= View::e($transaction['party_name']) ?></a>
                  <?php if ($isVoid): ?><span class="void-badge">ملغاة</span><?php endif; ?>
                </td>
                <td class="table-type" data-label="النوع"><?= View::e(Formatter::type($transaction['type'])) ?></td>
                <td class="table-details" data-label="التفاصيل">
                  <?php if ($transaction['weight'] !== null): ?>
                    <?= View::e($transaction['weight']) ?> كجم × <?= View::e(Formatter::money($transaction['unit_price'])) ?> جنيه
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="table-meta" data-label="بواسطة / التاريخ">
                  <span><?= View::e($transaction['creator_name']) ?></span>
                  <time datetime="<?= View::e($transaction['tx_date']) ?>"><?= View::e($transaction['tx_date']) ?></time>
                </td>
                <td class="table-note" data-label="ملاحظة"><?= !empty($transaction['note']) ? View::e($transaction['note']) : '—' ?></td>
                <td class="table-amount" data-label="المبلغ">
                  <?php if (!$isVoid): ?>
                    <strong class="transaction-amount <?= $isDebit ? 'text-debit' : 'text-credit' ?>">
                      <?= View::e(Formatter::money($transaction['amount'])) ?> <small>جنيه</small>
                    </strong>
                    <span class="balance-badge <?= $isDebit ? 'is-owed' : 'is-received' ?>"><?= $isDebit ? 'عليه' : 'له' ?></span>
                  <?php else: ?>
                    <span class="void-badge">ملغاة</span>
                  <?php endif; ?>
                </td>
                <td class="table-action" data-label="إجراء">
                  <?php if (!$isVoid && $isOwner): ?>
                    <details class="void-details">
                      <summary>إلغاء الحركة</summary>
                      <form action="<?= View::e(Url::to('/transactions/' . (int) $transaction['id'] . '/void')) ?>" method="post" data-confirm="هل تريد إلغاء هذه الحركة؟ إذا كانت مرتبطة بدفع فوري فسيتم إلغاؤهما معًا." class="void-form">
                        <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                        <input name="reason" maxlength="255" aria-label="سبب الإلغاء (اختياري)" placeholder="سبب الإلغاء (اختياري)">
                        <button type="submit">تأكيد الإلغاء</button>
                      </form>
                    </details>
                  <?php else: ?>—<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="history-pagination">
      <span>عرض <?= number_format($startRecord) ?>–<?= number_format($endRecord) ?> من <?= number_format($totalTransactions) ?></span>
      <div>
        <?php if ($page > 1): ?>
          <a href="<?= View::e($historyPageUrl($page - 1)) ?>">السابق</a>
        <?php else: ?>
          <span class="is-disabled">السابق</span>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a href="<?= View::e($historyPageUrl($page + 1)) ?>">التالي</a>
        <?php else: ?>
          <span class="is-disabled">التالي</span>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
