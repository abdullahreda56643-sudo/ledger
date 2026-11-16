<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

/* ==========================================================
   1) المنطق (كان في Layout.php)
   ========================================================== */
$user        = Auth::user();
$isOwner     = ($user['role'] ?? '') === 'owner';
$currentPath = Url::requestPath();
$pageTitle   = $pageTitle ?? '';
$title       = $pageTitle !== '' ? $pageTitle . ' - ' : '';
$appName     = (string) ($GLOBALS['config']['app']['name'] ?? 'دفتر الحساب');

$nav = [
    ['path' => '/',        'label' => 'التشغيل اليومي'],
    ['path' => '/parties', 'label' => 'الأطراف'],
];
if ($isOwner) {
    $nav[] = ['path' => '/reports/debtors', 'label' => 'التقارير'];
    $nav[] = ['path' => '/users',           'label' => 'إدارة المستخدمين'];
    $nav[] = ['path' => '/audit',           'label' => 'سجل التدقيق'];
}

$description = match (true) {
    $currentPath === '/'                          => 'تسجيل حركات العملاء والموردين ومتابعة سجل اليوم',
    $currentPath === '/parties'                   => 'إدارة العملاء والموردين ومراجعة أرصدتهم',
    $currentPath === '/reports/debtors'           => 'متابعة الأطراف ذات الأرصدة المستحقة',
    $currentPath === '/users'                     => 'إدارة حسابات العاملين وصلاحياتهم',
    $currentPath === '/audit'                     => 'مراجعة سجل الأنشطة والتغييرات',
    str_starts_with($currentPath, '/statement')   => 'مراجعة الحركات والرصيد الحالي للطرف',
    default                                       => 'إدارة الحسابات اليومية بكل وضوح',
};

$isActive = static function (string $current, string $navPath): bool {
    if ($navPath === '/') {
        return $current === '/';
    }
    return $current === $navPath || str_starts_with($current, $navPath . '/');
};

$todayLabel = null;
if ($currentPath === '/') {
    $dayNames   = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $monthNames = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
                   'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $todayLabel = $dayNames[(int) date('w')]
        . '، ' . date('j') . ' '
        . $monthNames[(int) date('n')] . ' '
        . date('Y');
}

/* ==========================================================
   2) أيقونات الهيدر
   ========================================================== */
$icons = [
    'moon' => '<span class="material-symbols-outlined theme-icon theme-icon-moon" aria-hidden="true">dark_mode</span>',
    'sun' => '<span class="material-symbols-outlined theme-icon theme-icon-sun" aria-hidden="true">light_mode</span>',
    'logout' => '<span class="material-symbols-outlined" aria-hidden="true">logout</span>',
    'book' => '<span class="material-symbols-outlined">menu_book</span>',
];

/* ==========================================================
   3) أنماط CSS للصفحة
   ========================================================== */
$pageStyles = [
    '/'                => '/assets/css/operation.css?v=9',
    '/reports/debtors' => '/assets/css/reports.css',
    '/users'           => '/assets/css/users.css',
    '/audit'           => '/assets/css/audit.css',
];
if (str_starts_with($currentPath, '/parties')) {
    $pageStyles['/parties'] = '/assets/css/parties.css?v=5';
}
$pageStyle = $pageStyles[$currentPath] ?? null;
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F7F8F5">
  <script>
    /* تطبيق المظهر المحفوظ قبل الرسم لتجنّب وميض الوضع الداكن */
    (function () {
      try {
        if (localStorage.getItem('ledger-theme') === 'dark') {
          document.documentElement.classList.add('dark');
          var meta = document.querySelector('meta[name="theme-color"]');
          if (meta) { meta.setAttribute('content', '#0E1513'); }
        }
      } catch (e) {}
    })();
  </script>
  <meta name="app-base-path" content="<?= View::e(Url::basePath()) ?>">
  <link rel="manifest" href="<?= View::e(Url::to('/assets/manifest.json')) ?>">
  <title><?= View::e($title . $appName) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet">

  <?php if ($pageStyle !== null): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to($pageStyle)) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/responsive.css?v=3')) ?>">
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/print.css?v=3')) ?>">

  <script defer src="<?= View::e(Url::to('/assets/js/app.js?v=4')) ?>"></script>

  <style>
    /* ==========================================================
       هيكل التطبيق — الهوية الزمردية/الذهبية
       أسماء متغيرات --shell-* محفوظة كما هي لتبقى ملفات التنسيق
       الأخرى (parties/reports/users/audit) متوافقة دون تعديل.
       ========================================================== */
    :root {
      color-scheme: light;
      --shell-primary: #0F5E47;
      --shell-primary-hover: #0B4B39;
      --shell-primary-contrast: #FFFFFF;
      --shell-primary-soft: #D8EDE4;
      --shell-gold: #B88A2C;
      --shell-gold-ink: #6B5120;
      --shell-gold-soft: rgb(184 138 44 / 12%);
      --shell-background: #F7F8F5;
      --shell-surface: #FFFFFF;
      --shell-surface-2: #F2F5F2;
      --shell-border: #DDE5E0;
      --shell-text: #161D1A;
      --shell-muted: #4B5953;
      --shell-credit: #146C4E;
      --shell-debit: #B44332;
      --shell-ring: rgb(15 94 71 / 16%);
      --shell-shadow: 0 12px 32px rgb(22 29 26 / 8%);
    }
    .dark {
      color-scheme: dark;
      --shell-primary: #47B894;
      --shell-primary-hover: #63C9A3;
      --shell-primary-contrast: #06291E;
      --shell-primary-soft: #134737;
      --shell-gold: #D8B15D;
      --shell-gold-ink: #E9BD73;
      --shell-gold-soft: rgb(216 177 93 / 12%);
      --shell-background: #0E1513;
      --shell-surface: #151E1B;
      --shell-surface-2: #192320;
      --shell-border: #26352F;
      --shell-text: #E8EFEA;
      --shell-muted: #A2B3AC;
      --shell-credit: #63D3A6;
      --shell-debit: #FF8878;
      --shell-ring: rgb(71 184 148 / 20%);
      --shell-shadow: 0 12px 32px rgb(0 0 0 / 30%);
    }

    html {
      min-width: 0;
      font-family: 'IBM Plex Sans Arabic', 'Tajawal', ui-sans-serif, system-ui, sans-serif;
      scroll-padding-top: 76px;
    }
    body {
      min-height: 100vh;
      background: var(--shell-background);
      color: var(--shell-text);
      font-size: 16px;
      font-weight: 500;
      font-variant-numeric: tabular-nums;
    }
    ::selection { background: rgb(15 94 71 / 22%); }
    .dark ::selection { background: rgb(71 184 148 / 28%); }

    *, *::before, *::after { box-sizing: border-box; }
    :focus-visible { outline: 3px solid var(--shell-primary); outline-offset: 3px; }
    .hidden { display: none !important; }

    .text-xs   { font-size: .8125rem; line-height: 1.45; }
    .text-sm   { font-size: .9375rem; line-height: 1.55; }
    .text-base { font-size: 1rem; line-height: 1.6; }
    .text-lg   { font-size: 1.1875rem; line-height: 1.5; }
    .text-xl   { font-size: 1.375rem; line-height: 1.4; }
    .text-2xl  { font-size: 1.5625rem; line-height: 1.35; }

    .app-shell { display: block; min-height: 100vh; }
    .app-main {
      min-width: 0;
      width: min(100%, 1280px);
      margin-inline: auto;
      padding: 24px clamp(12px, 2.5vw, 32px) 48px;
    }

    /* ============ Topbar ============ */
    .app-topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      border-bottom: 1px solid var(--shell-border);
      background: var(--shell-surface);
    }
    .app-topbar-inner {
      width: min(100%, 1280px);
      margin-inline: auto;
      padding-inline: clamp(16px, 2.5vw, 32px);
    }
    .topbar-main-row {
      display: flex;
      min-height: 64px;
      align-items: center;
      gap: clamp(12px, 2vw, 28px);
    }
    .topbar-brand-group { display: flex; flex: 0 0 auto; align-items: center; gap: 8px; }
    .app-brand {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 9px;
      color: var(--shell-text);
      text-decoration: none;
    }
    .app-brand:focus-visible { outline-offset: 3px; }
    .app-brand-mark {
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      border-radius: 6px;
      background: var(--shell-primary);
      color: var(--shell-primary-contrast);
      box-shadow: 0 1px 2px rgb(22 29 26 / 12%);
    }
    .app-brand-mark svg { width: 21px; height: 21px; }
    .app-brand-mark .material-symbols-outlined { font-size: 20px; }
    .app-brand-name { font-size: 18px; font-weight: 700; white-space: nowrap; }

    /* روابط التنقل — الشريط الأفقي والقائمة المتجاوبة */
    .topbar-link {
      display: inline-flex;
      min-height: 40px;
      align-items: center;
      padding: 8px 14px;
      border-radius: 6px;
      color: var(--shell-muted);
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      white-space: nowrap;
      transition: background-color 150ms, color 150ms;
    }
    .topbar-link:hover,
    .topbar-link.is-active { background: var(--shell-primary-soft); color: var(--shell-primary); }
    .topbar-link.is-active { position: relative; }
    .topbar-link.is-active::after {
      position: absolute;
      right: 12px;
      bottom: 0;
      left: 12px;
      height: 2px;
      border-radius: 2px;
      background: var(--shell-primary);
      content: "";
    }

    .topbar-nav {
      display: flex;
      min-width: 0;
      align-items: center;
      gap: 4px;
      margin-inline: auto;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .topbar-nav::-webkit-scrollbar { display: none; }

    .topbar-account {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 8px;
      padding-inline-start: 8px;
      border-inline-start: 1px solid var(--shell-border);
    }
    .topbar-account-copy { display: none; gap: 2px; text-align: start; }
    .topbar-account-name { color: var(--shell-text); font-size: 12px; font-weight: 700; white-space: nowrap; }
    .topbar-account-role { color: var(--shell-muted); font-size: 11px; white-space: nowrap; }
    .topbar-avatar {
      display: grid;
      width: 34px;
      height: 34px;
      place-items: center;
      border-radius: 50%;
      background: var(--shell-primary-soft);
      color: var(--shell-primary);
      font-size: 13px;
      font-weight: 700;
    }

    .topbar-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 8px; }
    .topbar-actions form { margin: 0; }

    .icon-button,
    .logout-button {
      display: inline-flex;
      min-height: 40px;
      align-items: center;
      justify-content: center;
      gap: 7px;
      border: 1px solid var(--shell-border);
      border-radius: 10px;
      background: var(--shell-surface);
      color: var(--shell-muted);
      font: inherit;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: border-color 150ms, background-color 150ms, color 150ms;
    }
    .icon-button:hover {
      border-color: color-mix(in srgb, var(--shell-primary) 45%, var(--shell-border));
      color: var(--shell-primary);
    }
    .icon-button svg,
    .logout-button svg { width: 17px; height: 17px; }
    .logout-button { color: var(--shell-debit); }
    .logout-button:hover {
      border-color: color-mix(in srgb, var(--shell-debit) 45%, var(--shell-border));
      background: #FBEAEC;
    }
    .topbar-actions .icon-button,
    .topbar-actions .logout-button { min-height: 36px; border-radius: 6px; }
    .topbar-actions .icon-button { width: 36px; padding: 0; }
    .topbar-actions .logout-button .material-symbols-outlined { font-size: 18px; }

    .theme-icon { display: block; width: 18px; height: 18px; font-size: 18px; line-height: 1; }
    .theme-icon-sun { display: none; }
    .dark .theme-icon-moon { display: none; }
    .dark .theme-icon-sun { display: block; }

    .topbar-menu-toggle,
    .topbar-mobile-nav { display: none; }
    .topbar-mobile-nav-footer { display: none; }
    .topbar-menu-toggle {
      width: 36px;
      height: 36px;
      flex: 0 0 auto;
      place-items: center;
      border: 1px solid var(--shell-border);
      border-radius: 6px;
      background: transparent;
      color: var(--shell-text);
      cursor: pointer;
    }
    .topbar-menu-toggle .material-symbols-outlined { font-size: 20px; }
    .topbar-mobile-logout {
      flex: 0 0 auto;
      border: 0;
      background: transparent;
      color: var(--shell-debit);
      font: inherit;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
    }

    /* ============ Page header ============ */
    .main-page-heading {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 32px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--shell-border);
    }
    .page-breadcrumb {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 4px;
      color: var(--shell-muted);
      font-size: 12px;
    }
    .page-breadcrumb span:last-child { color: var(--shell-primary); }
    .main-page-title {
      margin: 0;
      color: var(--shell-text);
      font-size: 24px;
      font-weight: 700;
      line-height: 1.42;
    }
    .page-description {
      margin: 2px 0 0;
      color: var(--shell-muted);
      font-size: 13px;
      line-height: 1.55;
    }
    .page-date {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 8px;
      padding: 7px 12px;
      border: 1px solid var(--shell-border);
      border-radius: 7px;
      background: var(--shell-surface);
      color: var(--shell-text);
      font-size: 12px;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }
    .page-date > span {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--shell-gold);
    }

    /* ============ Flash messages ============ */
    .flash-message {
      margin-bottom: 16px;
      border: 1px solid transparent;
      border-radius: 14px;
      padding: 13px 16px;
      font-size: 15px;
      line-height: 1.7;
    }
    .flash-success { border-color: #B9DDCB; background: #E7F4EE; color: #1B7F5C; }
    .flash-error { border-color: #F1C4C9; background: #FBEAEC; color: #B42333; }
    .flash-error ul { margin: 0; padding-inline-start: 20px; }

    /* ============ Offline banner ============ */
    #offline-banner:not(.hidden) {
      position: relative;
      z-index: 20;
      border-bottom: 1px solid #E8D5A2;
      background: linear-gradient(135deg, #FBF3DC, #F8EFD8);
      color: #7A5A14;
    }

    /* ============ Dark overrides ============ */
    .dark .surface { background: var(--shell-surface); border-color: var(--shell-border); }
    .dark .flash-success { border-color: #315B49; background: #12291F; color: #5FD0A0; }
    .dark .flash-error { border-color: #65363D; background: #3A252B; color: #FF8B97; }
    .dark .logout-button:hover { border-color: var(--shell-debit); background: #3A252B; }
    .dark #offline-banner:not(.hidden) { border-color: #55481F; background: #26200F; color: #E4C878; }

    /* ============ Responsive ============ */
    @media (min-width: 1280px) {
      .topbar-account-copy { display: grid; }
      .topbar-actions .logout-button { width: auto; padding-inline: 10px; }
      .topbar-actions .logout-button span { display: inline; }
    }
    @media (max-width: 1199px) {
      .topbar-main-row { gap: 16px; }
    }
    @media (max-width: 1023px) {
      .app-topbar-inner { width: 100%; padding: 0; }
      .topbar-main-row { min-height: 56px; justify-content: space-between; gap: 12px; padding: 8px 16px; }
      .topbar-brand-group { gap: 8px; }
      .app-brand { gap: 4px; }
      .app-brand-mark { width: 28px; height: 28px; box-shadow: none; }
      .app-brand-mark .material-symbols-outlined { font-size: 16px; }
      .app-brand-name { font-size: 15px; }
      .topbar-nav { display: none; }
      .topbar-menu-toggle { display: grid; }
      .topbar-account { gap: 0; padding: 0; border: 0; }
      .topbar-account-copy,
      .topbar-logout-form { display: none; }
      .topbar-avatar { width: 32px; height: 32px; font-size: 12px; }
      .topbar-actions { gap: 8px; direction: ltr; }
      .topbar-actions .icon-button { width: 36px; min-height: 36px; padding: 0; }
      .topbar-mobile-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
        border-top: 1px solid var(--shell-border);
        background: var(--shell-surface);
        padding: 8px 16px;
      }
      .topbar-mobile-nav.hidden { display: none; }
      .topbar-mobile-nav .topbar-link { width: 100%; min-height: 44px; padding: 10px 12px; }
      .topbar-mobile-nav .topbar-link.is-active::after { display: none; }
      .topbar-mobile-nav-footer {
        display: flex;
        min-height: 44px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-top: 1px solid var(--shell-border);
        margin-top: 4px;
        padding: 8px 12px 0;
      }
      .topbar-mobile-user { overflow: hidden; color: var(--shell-muted); font-size: 13px; text-overflow: ellipsis; white-space: nowrap; }
      .app-main { padding: 16px 14px 36px; }
    }
    @media (max-width: 600px) {
      html { scroll-padding-top: 56px; }
      .main-page-heading { align-items: flex-start; flex-direction: column; gap: 8px; margin-bottom: 22px; padding-bottom: 12px; }
      .page-breadcrumb,
      .page-description { display: none; }
      .main-page-title { font-size: 20px; }
      .page-date { border: 0; margin-top: 2px; padding: 0; font-size: 13px; }
      .page-date > span { display: none; }
    }
    @media (max-width: 300px) {
      .app-brand-name { display: none; }
      .topbar-actions { gap: 4px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
      }
    }
  </style>
</head>
<body>
  <div id="offline-banner" class="offline-banner hidden" role="status">الاتصال غير متاح. لم يتم حفظ أي إدخال دون اتصال؛ تحقق من الشبكة قبل إعادة المحاولة.</div>

  <header class="app-topbar">
    <div class="app-topbar-inner">
      <div class="topbar-main-row">
        <div class="topbar-brand-group">
          <button type="button" class="topbar-menu-toggle" data-mobile-nav-toggle aria-expanded="false" aria-controls="topbar-mobile-nav" aria-label="فتح القائمة">
            <span class="material-symbols-outlined" data-menu-icon aria-hidden="true">menu</span>
          </button>
          <a class="app-brand" href="<?= View::e(Url::to('/')) ?>">
            <span class="app-brand-mark" aria-hidden="true"><?= $icons['book'] ?></span>
            <span class="app-brand-name"><?= View::e($appName) ?></span>
          </a>
        </div>

        <nav class="topbar-nav" aria-label="التنقل الرئيسي">
          <?php foreach ($nav as $item): ?>
            <?php $active = $isActive($currentPath, $item['path']); ?>
            <a class="topbar-link <?= $active ? 'is-active' : '' ?>"
               href="<?= View::e(Url::to($item['path'])) ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
              <?= View::e($item['label']) ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <div class="topbar-actions">
          <div class="topbar-account">
            <span class="topbar-account-copy">
              <span class="topbar-account-name"><?= View::e($user['name'] ?? '') ?></span>
              <span class="topbar-account-role"><?= $isOwner ? 'مالك المحل' : 'موظف' ?></span>
            </span>
            <span class="topbar-avatar" aria-hidden="true"><?= View::e(mb_substr($user['name'] ?? 'م', 0, 1)) ?></span>
          </div>
          <button type="button" data-theme-toggle class="icon-button" aria-label="تغيير المظهر" title="تغيير المظهر">
            <?= $icons['moon'] ?><?= $icons['sun'] ?>
          </button>
          <form id="logout-form" class="topbar-logout-form" action="<?= View::e(Url::to('/logout')) ?>" method="post">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button class="logout-button" type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج">
              <?= $icons['logout'] ?><span>خروج</span>
            </button>
          </form>
        </div>
      </div>

      <nav id="topbar-mobile-nav" class="topbar-mobile-nav hidden" aria-label="قائمة التنقل">
        <?php foreach ($nav as $item): ?>
          <?php $active = $isActive($currentPath, $item['path']); ?>
          <a class="topbar-link <?= $active ? 'is-active' : '' ?>"
             href="<?= View::e(Url::to($item['path'])) ?>"
             <?= $active ? 'aria-current="page"' : '' ?>>
            <?= View::e($item['label']) ?>
          </a>
        <?php endforeach; ?>
        <div class="topbar-mobile-nav-footer">
          <span class="topbar-mobile-user"><?= View::e($user['name'] ?? '') ?> · <?= $isOwner ? 'مالك المحل' : 'موظف' ?></span>
          <button type="submit" form="logout-form" class="topbar-mobile-logout">تسجيل الخروج</button>
        </div>
      </nav>
    </div>
  </header>

  <div class="app-shell">
    <main class="app-main">
      <header class="main-page-heading">
        <div>
          <nav aria-label="مسار التنقل" class="page-breadcrumb">
            <span>دفتر الحساب</span>
            <span aria-hidden="true">/</span>
            <span><?= View::e($pageTitle) ?></span>
          </nav>
          <h1 class="main-page-title"><?= View::e($pageTitle) ?></h1>
          <p class="page-description"><?= View::e($description) ?></p>
        </div>
        <?php if ($todayLabel !== null): ?>
          <div class="page-date">
            <span aria-hidden="true"></span>
            <time datetime="<?= View::e(date('Y-m-d')) ?>"><?= View::e($todayLabel) ?></time>
          </div>
        <?php endif; ?>
      </header>

      <?php if (!empty($success)): ?>
        <div class="flash-message flash-success" role="status"><?= View::e($success) ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="flash-message flash-error" role="alert">
          <ul><?php foreach ($errors as $error): ?><li><?= View::e($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>
</body>
</html>