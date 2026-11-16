<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$old = $old ?? [];
$defaultCategory = (string) ($old['category_code'] ?? ($categories[0]['code'] ?? ''));
?>
<section class="unit-settings-page" aria-label="إعدادات وحدات القياس">
  <div class="unit-settings-intro">
    <div>
      <span class="unit-settings-eyebrow">إعدادات النظام</span>
      <p>تحكم في وحدات الكمية ومعامل تحويلها، مع الحفاظ على الوحدة المستخدمة في كل حركة سابقة.</p>
    </div>
    <a class="unit-add-shortcut" href="#add-unit">
      <span class="material-symbols-outlined" aria-hidden="true">add</span>
      <span>إضافة وحدة</span>
    </a>
  </div>

  <div class="unit-settings-notice" role="note">
    <span class="material-symbols-outlined" aria-hidden="true">swap_horiz</span>
    <p>يُحسب المبلغ حسب الكمية المحوّلة إلى وحدة الأساس، والسعر المدخل لكل وحدة أساس. لا يمكن التحويل بين فئات مختلفة.</p>
  </div>

  <div class="unit-category-list">
    <?php foreach ($categories as $category): ?>
      <?php
        $categoryUnits = $category['units'];
        $activeCount = count(array_filter(
            $categoryUnits,
            static fn (array $unit): bool => (int) $unit['is_active'] === 1
        ));
      ?>
      <section class="unit-category-card" aria-labelledby="unit-category-<?= View::e($category['code']) ?>">
        <header class="unit-category-heading">
          <div>
            <span class="unit-category-icon material-symbols-outlined" aria-hidden="true">
              <?= $category['code'] === 'mass' ? 'scale' : ($category['code'] === 'count' ? 'inventory_2' : ($category['code'] === 'volume' ? 'water_drop' : ($category['code'] === 'length' ? 'straighten' : 'square_foot'))) ?>
            </span>
            <div>
              <h2 id="unit-category-<?= View::e($category['code']) ?>"><?= View::e($category['name']) ?></h2>
              <p>وحدة الأساس: <?= View::e($category['base_unit_name']) ?> (<?= View::e($category['base_unit_symbol']) ?>)</p>
            </div>
          </div>
          <span class="unit-category-count"><?= number_format($activeCount) ?> نشطة</span>
        </header>

        <?php if ($categoryUnits === []): ?>
          <div class="unit-empty">لا توجد وحدات في هذه الفئة بعد.</div>
        <?php else: ?>
          <div class="unit-list">
            <?php foreach ($categoryUnits as $unit): ?>
              <?php
                $isActive = (int) $unit['is_active'] === 1;
                $isDefault = (int) $unit['is_default'] === 1;
                $isBase = (int) $unit['is_base'] === 1;
              ?>
              <form class="unit-row" method="post" action="<?= View::e(Url::to('/settings/units/' . (int) $unit['id'])) ?>">
                <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                <div class="unit-field unit-name-field">
                  <label for="unit-name-<?= (int) $unit['id'] ?>">اسم الوحدة</label>
                  <input id="unit-name-<?= (int) $unit['id'] ?>" name="name" maxlength="60" required value="<?= View::e($unit['name']) ?>">
                </div>
                <div class="unit-field unit-symbol-field">
                  <label for="unit-symbol-<?= (int) $unit['id'] ?>">الرمز</label>
                  <input id="unit-symbol-<?= (int) $unit['id'] ?>" name="symbol" maxlength="24" required value="<?= View::e($unit['symbol']) ?>">
                </div>
                <div class="unit-field unit-factor-field">
                  <label for="unit-factor-<?= (int) $unit['id'] ?>">مقابل وحدة الأساس</label>
                  <input id="unit-factor-<?= (int) $unit['id'] ?>" name="factor_to_base" type="number" step="0.000001" min="0.000001" max="1000000" required value="<?= View::e($unit['factor_to_base']) ?>" <?= $isBase ? 'readonly aria-describedby="base-unit-hint-' . (int) $unit['id'] . '"' : '' ?>>
                  <?php if ($isBase): ?><small id="base-unit-hint-<?= (int) $unit['id'] ?>">ثابتة لوحدة الأساس</small><?php endif; ?>
                </div>
                <div class="unit-row-options">
                  <label class="unit-checkbox">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" <?= $isDefault ? 'checked' : '' ?> <?= !$isActive ? 'disabled' : '' ?>>
                    <span>افتراضية</span>
                  </label>
                  <label class="unit-checkbox">
                    <input type="hidden" name="is_active" value="<?= $isBase ? '1' : '0' ?>">
                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?> <?= $isBase ? 'disabled' : '' ?>>
                    <span><?= $isBase ? 'وحدة أساس' : 'نشطة' ?></span>
                  </label>
                </div>
                <span class="unit-status <?= $isActive ? 'is-active' : 'is-inactive' ?>">
                  <i aria-hidden="true"></i><?= $isActive ? 'متاحة للتشغيل' : 'متوقفة' ?>
                </span>
                <button class="unit-save-button" type="submit">حفظ</button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>

  <section class="unit-add-card" id="add-unit" aria-labelledby="add-unit-heading">
    <header>
      <span class="material-symbols-outlined" aria-hidden="true">add_circle</span>
      <div>
        <h2 id="add-unit-heading">إضافة وحدة مخصصة</h2>
        <p>أضف وحدة جديدة إلى إحدى الفئات الحالية وحدد قيمتها بوحدة الأساس.</p>
      </div>
    </header>
    <form method="post" action="<?= View::e(Url::to('/settings/units')) ?>" class="unit-add-form">
      <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
      <label class="unit-field">
        <span>اسم الوحدة</span>
        <input required maxlength="60" name="name" value="<?= View::e($old['name'] ?? '') ?>" placeholder="مثال: صندوق">
      </label>
      <label class="unit-field">
        <span>الرمز</span>
        <input required maxlength="24" name="symbol" value="<?= View::e($old['symbol'] ?? '') ?>" placeholder="مثال: صندوق">
      </label>
      <label class="unit-field">
        <span>الفئة</span>
        <select name="category_code" required>
          <?php foreach ($categories as $category): ?>
            <option value="<?= View::e($category['code']) ?>" <?= $defaultCategory === $category['code'] ? 'selected' : '' ?>>
              <?= View::e($category['name']) ?> — الأساس: <?= View::e($category['base_unit_symbol']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="unit-field">
        <span>مقدارها بوحدة الأساس</span>
        <input required type="number" step="0.000001" min="0.000001" max="1000000" name="factor_to_base" value="<?= View::e($old['factor_to_base'] ?? '1') ?>">
        <small>مثال: 1 جرام = 0.001 كجم</small>
      </label>
      <label class="unit-checkbox unit-add-default">
        <input type="checkbox" name="is_default" value="1">
        <span>اجعلها الوحدة الافتراضية لهذه الفئة</span>
      </label>
      <button class="unit-add-submit" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">add</span>
        إضافة الوحدة
      </button>
    </form>
  </section>

  <p class="unit-history-note">
    <span class="material-symbols-outlined" aria-hidden="true">history</span>
    لا تُحذف الوحدات بعد استخدامها؛ إيقاف الوحدة يمنع اختيارها للحركات الجديدة ويحافظ على تفاصيل الحركات السابقة.
  </p>
</section>
