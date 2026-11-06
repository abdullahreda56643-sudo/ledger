<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$values = $old !== [] ? $old : ($party ?? []);
$isEdit = $party !== null;
$action = Url::to($isEdit ? '/parties/' . (int) $party['id'] : '/parties');
?>
<section class="party-form-page" aria-labelledby="party-form-heading">
  <div class="party-form-intro">
    <a class="party-back-link" href="<?= View::e(Url::to('/parties')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
      <span>العودة إلى قائمة الأطراف</span>
    </a>
  </div>

  <div class="party-form-card" aria-labelledby="party-form-heading">
    <div class="party-form-card-heading">
      <span class="party-form-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM17 8h5m-2.5-2.5v5"/></svg>
      </span>
      <div>
        <h2 id="party-form-heading">بيانات الطرف</h2>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="party-form-errors" role="alert">
        <strong>يرجى مراجعة البيانات التالية:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= View::e($error) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= View::e($action) ?>" class="party-form">
      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">

      <label class="party-form-field">
        <span>اسم الطرف <b aria-hidden="true">*</b></span>
        <input required maxlength="150" name="name" value="<?= View::e($values['name'] ?? '') ?>" autocomplete="name" placeholder="مثال: محمد أحمد" autofocus>
      </label>

      <label class="party-form-field">
        <span>رقم الهاتف <b aria-hidden="true">*</b></span>
        <input required inputmode="tel" type="tel" maxlength="20" name="phone" placeholder="01012345678" value="<?= View::e($values['phone'] ?? '') ?>" autocomplete="tel" dir="ltr">
      </label>

      <label class="party-form-field">
        <span>نوع الطرف <b aria-hidden="true">*</b></span>
        <span class="party-select-wrap">
          <select name="kind">
            <?php foreach (['customer' => 'عميل', 'supplier' => 'مورد', 'both' => 'عميل ومورد'] as $key => $label): ?>
              <option value="<?= View::e($key) ?>" <?= ($values['kind'] ?? 'customer') === $key ? 'selected' : '' ?>><?= View::e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </span>
      </label>

      <label class="party-form-field party-form-notes">
        <span>ملاحظات <small>(اختياري)</small></span>
        <textarea maxlength="255" name="notes" rows="3" placeholder="أضف تفاصيل مختصرة (اختياري)"><?= View::e($values['notes'] ?? '') ?></textarea>
      </label>

      <div class="party-form-actions">
        <button type="submit" class="party-form-submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7"/></svg>
          <span><?= $isEdit ? 'حفظ التعديلات' : 'حفظ الطرف' ?></span>
        </button>
        <a href="<?= View::e(Url::to('/parties')) ?>" class="party-form-cancel">إلغاء</a>
      </div>
    </form>
  </div>
</section>
