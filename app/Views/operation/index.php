<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$old = $old ?? [];
$type = $old['type'] ?? 'sell';
$today = date('Y-m-d');
$usesWeight = in_array($type, ['sell', 'supply'], true);

/* أيقونات أنواع الحركات (للعرض فقط) */
$typeIcons = [
    'sell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14m-6-6 6 6 6-6"/></svg>',
    'pay' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
    'supply' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5m-6 6 6-6 6 6"/></svg>',
    'pay_supplier' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>',
];
?>
<section class="operation-layout" aria-label="التشغيل اليومي">
  <section class="operation-panel" aria-labelledby="operation-form-title">
    <header class="panel-heading">
      <div>
        <span class="panel-eyebrow">إدخال جديد</span>
        <h2 id="operation-form-title">تسجيل حركة</h2>
        <p>أدخل تفاصيل الحركة لتحديث رصيد الطرف مباشرة.</p>
      </div>
      <span class="panel-heading-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/>
          <path d="M14 4v5h4M9 14h6m-3-3v6"/>
        </svg>
      </span>
    </header>

    <form action="<?= View::e(Url::to('/transactions')) ?>" method="post" data-operation-form class="operation-form">
      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">

      <fieldset class="operation-fieldset">
        <legend class="field-heading">نوع الحركة</legend>
        <div class="operation-types">
          <?php foreach (['sell' => 'سحب', 'pay' => 'دفعة من الطرف', 'supply' => 'توريد', 'pay_supplier' => 'دفعة لمورد'] as $value => $label): ?>
            <label class="operation-type <?= $type === $value ? 'is-selected' : '' ?>">
              <input class="visually-hidden" type="radio" name="type" value="<?= View::e($value) ?>" <?= $type === $value ? 'checked' : '' ?> data-operation-type>
              <span class="type-icon" aria-hidden="true"><?= $typeIcons[$value] ?></span>
              <span><?= View::e($label) ?></span>
              <span class="type-indicator" aria-hidden="true"></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="field-group">
        <label class="field-heading" for="party-search">الطرف</label>
        <div class="party-search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
          </svg>
          <input id="party-search" type="search" placeholder="ابحث بالاسم أو رقم الهاتف" autocomplete="off">
        </div>
        <select id="party-id" name="party_id" required aria-label="اختر الطرف">
          <option value="">اختر الطرف من القائمة</option>
          <?php foreach ($parties as $party): ?>
            <option value="<?= (int) $party['id'] ?>" data-search="<?= View::e($party['name'] . ' ' . $party['phone']) ?>" <?= (string) ($old['party_id'] ?? '') === (string) $party['id'] ? 'selected' : '' ?>><?= View::e($party['name'] . ' — ' . $party['phone']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div data-weight-fields class="<?= $usesWeight ? '' : 'hidden' ?> amount-fields">
        <label class="field-group">الوزن <span class="field-unit">(كجم)</span>
          <input name="weight" type="number" inputmode="decimal" min="0.001" max="1000" step="0.001" value="<?= View::e($old['weight'] ?? '') ?>" data-weight placeholder="مثال: 12.500">
        </label>
        <label class="field-group">السعر للكيلو <span class="field-unit">(جنيه)</span>
          <input name="unit_price" type="number" inputmode="decimal" min="0.01" step="0.01" value="<?= View::e($old['unit_price'] ?? '') ?>" data-price placeholder="أدخل سعر الكيلو">
        </label>
        <div class="estimated-total" aria-live="polite">
          <span>الإجمالي التقديري <span data-total-direction>عليه</span></span>
          <strong><b data-total>0</b> <small>جنيه</small></strong>
        </div>
      </div>

      <div data-amount-fields class="<?= $usesWeight ? 'hidden' : '' ?> field-group">
        <label for="operation-amount" class="field-heading">المبلغ <span class="field-unit">(جنيه)</span></label>
        <input id="operation-amount" name="amount" type="number" inputmode="decimal" min="0.01" step="0.01" value="<?= View::e($old['amount'] ?? '') ?>" data-amount placeholder="أدخل مبلغ الحركة">
      </div>

      <div data-pay-now-wrap class="<?= $type === 'sell' ? '' : 'hidden' ?> field-group">
        <label for="pay-now" class="field-heading">دفع الآن <span class="field-unit">(اختياري)</span></label>
        <input id="pay-now" name="pay_now" type="number" inputmode="decimal" min="0.01" step="0.01" value="<?= View::e($old['pay_now'] ?? '') ?>" data-pay-now placeholder="اتركه فارغًا إن لم يوجد دفع">
        <span class="field-hint">يُخصم هذا المبلغ من إجمالي الحركة.</span>
      </div>

      <div class="form-details">
        <?php if ((Auth::user()['role'] ?? '') === 'owner'): ?>
          <label class="field-group">تاريخ الحركة
            <input name="tx_date" type="date" max="<?= View::e($today) ?>" value="<?= View::e($old['tx_date'] ?? $today) ?>">
          </label>
        <?php else: ?>
          <input type="hidden" name="tx_date" value="<?= View::e($today) ?>">
        <?php endif; ?>
        <label class="field-group">ملاحظة <span class="field-unit">(اختياري)</span>
          <input name="note" maxlength="255" value="<?= View::e($old['note'] ?? '') ?>" placeholder="أضف ملاحظة للحركة">
        </label>
      </div>

      <section class="balance-preview" aria-label="معاينة الرصيد" aria-live="polite">
        <div>
          <span>الرصيد الحالي</span>
          <strong data-current-balance>اختر طرفًا</strong>
        </div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        <div>
          <span>بعد الحركة</span>
          <strong data-next-balance>—</strong>
        </div>
      </section>

      <button class="save-operation" type="submit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        <span>حفظ الحركة</span>
      </button>
    </form>
  </section>

  <section class="daily-panel" aria-labelledby="daily-activity-title">
    <header class="panel-heading daily-heading">
      <div>
        <span class="panel-eyebrow">ملخص اليوم</span>
        <h2 id="daily-activity-title">حركات اليوم</h2>
        <p><?= View::e(date('d/m/Y')) ?> <span class="today-status"><i aria-hidden="true"></i> اليوم</span></p>
      </div>
      <span class="transaction-count"><?= count($transactions) ?> حركة</span>
    </header>

    <?php if ($transactions === []): ?>
      <div class="empty-activity">
        <span class="empty-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/><path d="M14 4v5h4M9 14h6m-3-3v6"/></svg>
        </span>
        <h3>لسه مفيش حركات</h3>
        <p>أول ما تسجل حركة هتظهر هنا في ملخص اليوم.</p>
        <a href="#operation-form-title">ابدأ بتسجيل حركة</a>
      </div>
    <?php else: ?>
      <ul class="transaction-list">
        <?php foreach ($transactions as $transaction): ?>
          <?php
          $isVoid = (int) $transaction['is_void'] === 1;
          $isDebit = $transaction['direction'] === 'debit';
          ?>
          <li class="transaction-item <?= $isVoid ? 'is-void' : '' ?>">
            <span class="transaction-icon <?= $isDebit ? 'is-debit' : 'is-credit' ?>" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <?php if ($isDebit): ?><path d="M12 5v14m-6-6 6 6 6-6"/>
                <?php else: ?><path d="M12 19V5m-6 6 6-6 6 6"/><?php endif; ?>
              </svg>
            </span>
            <div class="transaction-info">
              <a class="party-name" href="<?= View::e(Url::to('/statement/' . (int) $transaction['party_id'])) ?>"><?= View::e($transaction['party_name']) ?></a>
              <span class="transaction-description">
                <?= View::e(Formatter::type($transaction['type'])) ?>
                <?php if ($transaction['weight'] !== null): ?> · <?= View::e($transaction['weight']) ?> كجم × <?= View::e(Formatter::money($transaction['unit_price'])) ?><?php endif; ?>
              </span>
              <span class="transaction-meta"><?= View::e($transaction['creator_name']) ?> · <?= View::e($transaction['tx_date']) ?></span>
              <?php if (!empty($transaction['note'])): ?><span class="transaction-note"><?= View::e($transaction['note']) ?></span><?php endif; ?>
            </div>
            <div class="transaction-side">
              <?php if ($isVoid): ?>
                <span class="void-badge">ملغاة</span>
              <?php else: ?>
                <strong class="transaction-amount <?= $isDebit ? 'text-debit' : 'text-credit' ?>">
                  <?= $isDebit ? 'عليه' : 'له' ?> <?= View::e(Formatter::money($transaction['amount'])) ?>
                  <small>جنيه</small>
                </strong>
              <?php endif; ?>
              <?php if (!$isVoid && (Auth::user()['role'] ?? '') === 'owner'): ?>
                <details class="void-details">
                  <summary>إلغاء الحركة</summary>
                  <form action="<?= View::e(Url::to('/transactions/' . (int) $transaction['id'] . '/void')) ?>" method="post" data-confirm="هل تريد إلغاء هذه الحركة؟ إذا كانت مرتبطة بدفع فوري فسيتم إلغاؤهما معًا." class="void-form">
                    <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                    <input name="reason" maxlength="255" aria-label="سبب الإلغاء (اختياري)" placeholder="سبب الإلغاء (اختياري)">
                    <button type="submit">تأكيد الإلغاء</button>
                  </form>
                </details>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</section>
