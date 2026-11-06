<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$activeCount = count(array_filter($users, static fn (array $user): bool => (int) $user['is_active'] === 1));
$workerCount = count(array_filter($users, static fn (array $user): bool => $user['role'] === 'worker'));
?>
<section class="users-page" aria-label="إدارة حسابات المستخدمين">
  <div class="users-intro">
    <div>
      <span class="users-eyebrow">إدارة الوصول</span>
      <p>أنشئ حسابات العاملين وتحكم في إمكانية دخولهم للنظام.</p>
    </div>
    <a class="users-add-link" href="#create-user" aria-label="إضافة عامل">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
      <span>إضافة عامل</span>
    </a>
  </div>

  <div class="users-overview" aria-label="ملخص الحسابات">
    <article class="users-stat">
      <span class="users-stat-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM16 5.2a3.25 3.25 0 0 1 0 6.3M17 14h.75a3.5 3.5 0 0 1 3.5 3.5V19"/></svg>
      </span>
      <span><small>إجمالي الحسابات</small><strong><?= number_format(count($users)) ?></strong></span>
    </article>
    <article class="users-stat">
      <span class="users-stat-icon users-stat-active" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7"/></svg>
      </span>
      <span><small>حسابات نشطة</small><strong><?= number_format($activeCount) ?></strong></span>
    </article>
    <article class="users-stat">
      <span class="users-stat-icon users-stat-workers" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.25h16M6.5 16V9.5h11V16M9 9.5V6.75h6V9.5M9 12.5h6"/></svg>
      </span>
      <span><small>حسابات العاملين</small><strong><?= number_format($workerCount) ?></strong></span>
    </article>
  </div>

  <div class="users-content">
    <section class="users-panel" aria-labelledby="accounts-title">
      <header class="users-panel-heading">
        <div>
          <h2 id="accounts-title">الحسابات المسجلة</h2>
          <p>إدارة حالة دخول كل حساب.</p>
        </div>
        <span class="users-total"><?= number_format(count($users)) ?> حساب</span>
      </header>

      <?php if ($users === []): ?>
        <div class="users-empty">
          <span aria-hidden="true">—</span>
          <p>لا توجد حسابات مستخدمين.</p>
        </div>
      <?php else: ?>
        <ul class="users-list">
          <?php foreach ($users as $user): ?>
            <?php $isActive = (int) $user['is_active'] === 1; $isOwner = $user['role'] === 'owner'; ?>
            <li class="user-row">
              <span class="user-avatar <?= $isOwner ? 'is-owner' : '' ?>" aria-hidden="true"><?= View::e(mb_substr($user['name'], 0, 1)) ?></span>
              <div class="user-details">
                <strong class="user-name"><?= View::e($user['name']) ?></strong>
                <span class="user-meta"><bdi><?= View::e($user['username']) ?></bdi><span aria-hidden="true">·</span><?= $isOwner ? 'مالك النظام' : 'عامل' ?></span>
              </div>
              <span class="user-status <?= $isActive ? 'is-active' : 'is-inactive' ?>">
                <i aria-hidden="true"></i><?= $isActive ? 'نشط' : 'موقوف' ?>
              </span>
              <?php if (!$isOwner): ?>
                <form method="post" action="<?= View::e(Url::to('/users/' . (int) $user['id'] . '/active')) ?>" data-confirm="<?= $isActive ? 'هل تريد إيقاف هذا الحساب؟' : 'هل تريد إعادة تفعيل هذا الحساب؟' ?>">
                  <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                  <input type="hidden" name="active" value="<?= $isActive ? '0' : '1' ?>">
                  <button class="user-toggle <?= $isActive ? 'is-deactivate' : 'is-activate' ?>" type="submit"><?= $isActive ? 'إيقاف' : 'تفعيل' ?></button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="users-panel create-user-panel" id="create-user" aria-labelledby="create-user-title">
      <header class="users-panel-heading">
        <div>
          <span class="create-user-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM17 8h5m-2.5-2.5v5"/></svg>
          </span>
          <span>
            <h2 id="create-user-title">إنشاء حساب عامل</h2>
            <p>أدخل بيانات الدخول للعامل الجديد.</p>
          </span>
        </div>
      </header>

      <form method="post" action="<?= View::e(Url::to('/users')) ?>" class="create-user-form">
        <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
        <label class="user-form-field">
          <span>اسم العامل</span>
          <input required maxlength="100" name="name" value="<?= View::e($old['name'] ?? '') ?>" autocomplete="name" placeholder="الاسم بالكامل">
        </label>
        <label class="user-form-field">
          <span>اسم الدخول</span>
          <input required minlength="3" maxlength="50" name="username" value="<?= View::e($old['username'] ?? '') ?>" autocomplete="off" dir="ltr" placeholder="username">
          <small>3–50 حرفًا إنجليزيًا أو رقمًا.</small>
        </label>
        <label class="user-form-field">
          <span>كلمة المرور</span>
          <input required minlength="12" maxlength="255" type="password" name="password" autocomplete="new-password" placeholder="12 حرفًا على الأقل">
          <small>استخدم كلمة مرور قوية وفريدة.</small>
        </label>
        <button class="create-user-submit" type="submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
          <span>إنشاء الحساب</span>
        </button>
      </form>
    </section>
  </div>
</section>
