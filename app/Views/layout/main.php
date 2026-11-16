<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Url;
use App\Core\View;

$user = Auth::user();
$isOwner = ($user['role'] ?? '') === 'owner';
$title = isset($pageTitle) ? $pageTitle . ' - ' : '';
$currentPath = Url::requestPath();
$nav = [
    ['/', 'التشغيل اليومي', 'home'],
    ['/parties', 'الأطراف', 'users'],
];
if ($isOwner) {
    $nav[] = ['/reports/debtors', 'التقارير', 'chart'];
    $nav[] = ['/users', 'إدارة المستخدمين', 'settings'];
    $nav[] = ['/audit', 'سجل التدقيق', 'activity'];
}
$pageDescriptions = [
    '/' => 'تسجيل حركات العملاء والموردين ومتابعة سجل اليوم',
    '/parties' => 'إدارة العملاء والموردين ومراجعة أرصدتهم',
    '/reports/debtors' => 'متابعة الأطراف ذات الأرصدة المستحقة',
    '/users' => 'إدارة حسابات العاملين وصلاحياتهم',
    '/audit' => 'مراجعة سجل الأنشطة والتغييرات',
];
$dayNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$monthNames = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$todayLabel = $dayNames[(int) date('w')] . '، ' . date('j') . ' ' . $monthNames[(int) date('n')] . ' ' . date('Y');
$pageDescription = $pageDescriptions[$currentPath] ?? (str_starts_with($currentPath, '/statement') ? 'مراجعة الحركات والرصيد الحالي للطرف' : 'إدارة الحسابات اليومية بكل وضوح');
$icons = [
    'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3.75 10 8.25-6.5 8.25 6.5"/><path d="M5.75 9.5v10.75h12.5V9.5M9.5 20.25v-6.5h5v6.5"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.25"/><path d="M3.75 19v-1.5A3.5 3.5 0 0 1 7.25 14h3.5a3.5 3.5 0 0 1 3.5 3.5V19ZM16 5.2a3.25 3.25 0 0 1 0 6.3M17 14h.75a3.5 3.5 0 0 1 3.5 3.5V19"/></svg>',
    'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.25h16"/><path d="M7 16V10m5 6V5m5 11v-4"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5.5 19a6.5 6.5 0 0 1 13 0M19 5v4m-2-2h4"/></svg>',
    'activity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12h4l2.5-7 4 14 2.5-7h4"/></svg>',
    'statement' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/><path d="M14 4v5h4m-8 4h4m-4 3h4"/></svg>',
    'moon' => '<svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.25 15.15A8.5 8.5 0 0 1 8.85 3.75 8.5 8.5 0 1 0 20.25 15.15Z"/></svg>',
    'sun' => '<svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2.75v2m0 14.5v2m9.25-9.25h-2m-14.5 0h-2m16.04-6.54-1.42 1.42M6.63 17.37l-1.42 1.42m13.58 0-1.42-1.42M6.63 6.63 5.21 5.21"/></svg>',
    'logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4.75H5.75v14.5H10M14 8l4 4-4 4m4-4H9"/></svg>',
];
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
  <title><?= View::e($title . $GLOBALS['config']['app']['name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet">
  <?php if ($currentPath === '/'): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/operation.css?v=6')) ?>">
  <?php endif; ?>
  <?php if (str_starts_with($currentPath, '/parties')): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/parties.css?v=4')) ?>">
  <?php endif; ?>
  <?php if ($currentPath === '/reports/debtors'): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/reports.css')) ?>">
  <?php endif; ?>
  <?php if ($currentPath === '/users'): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/users.css')) ?>">
  <?php endif; ?>
  <?php if ($currentPath === '/audit'): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/audit.css')) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/print.css?v=3')) ?>">
  <script defer src="<?= View::e(Url::to('/assets/js/app.js?v=3')) ?>"></script>
  <style>
    /* ==========================================================
       هيكل التطبيق — الهوية الزمرددية/الذهبية
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

    html { min-width: 0; font-family: 'IBM Plex Sans Arabic', 'Tajawal', ui-sans-serif, system-ui, sans-serif; }
    body { min-height: 100vh; background: var(--shell-background); color: var(--shell-text); font-size: 16px; font-weight: 500; font-variant-numeric: tabular-nums; }
    html { scroll-padding-top: 76px; }
    ::selection { background: rgb(15 94 71 / 22%); }
    .dark ::selection { background: rgb(71 184 148 / 28%); }
    .text-xs { font-size: .8125rem; line-height: 1.45; }
    .text-sm { font-size: .9375rem; line-height: 1.55; }
    .text-base { font-size: 1rem; line-height: 1.6; }
    .text-lg { font-size: 1.1875rem; line-height: 1.5; }
    .text-xl { font-size: 1.375rem; line-height: 1.4; }
    .text-2xl { font-size: 1.5625rem; line-height: 1.35; }
    .hidden { display: none !important; }
    *, *::before, *::after { box-sizing: border-box; }
    :focus-visible { outline: 3px solid var(--shell-primary); outline-offset: 3px; }

    .app-shell { display: block; min-height: 100vh; }
    .icon-button, .logout-button {
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
    .icon-button:hover { border-color: color-mix(in srgb, var(--shell-primary) 45%, var(--shell-border)); color: var(--shell-primary); }
    .icon-button svg, .logout-button svg { width: 17px; height: 17px; }
    .logout-button { color: var(--shell-debit); }
    .logout-button:hover { border-color: color-mix(in srgb, var(--shell-debit) 45%, var(--shell-border)); background: #FBEAEC; }

    .theme-icon { display: block; width: 18px; height: 18px; }
    .theme-icon-sun { display: none; }
    .dark .theme-icon-moon { display: none; }
    .dark .theme-icon-sun { display: block; }

    .app-main { min-width: 0; width: min(100%, 1280px); margin-inline: auto; padding: 24px clamp(12px, 2.5vw, 32px) 48px; }
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
    .page-breadcrumb { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; color: var(--shell-muted); font-size: 12px; }
    .page-breadcrumb span:last-child { color: var(--shell-primary); }
    .main-page-title { margin: 0; color: var(--shell-text); font-size: 24px; font-weight: 700; line-height: 1.42; }
    .page-description { margin: 2px 0 0; color: var(--shell-muted); font-size: 13px; line-height: 1.55; }
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
    .page-date > span { width: 7px; height: 7px; border-radius: 50%; background: var(--shell-gold); }

    .flash-message { margin-bottom: 16px; border: 1px solid transparent; border-radius: 14px; padding: 13px 16px; font-size: 15px; line-height: 1.7; }
    .flash-success { border-color: #B9DDCB; background: #E7F4EE; color: #1B7F5C; }
    .flash-error { border-color: #F1C4C9; background: #FBEAEC; color: #B42333; }
    .flash-error ul { margin: 0; padding-inline-start: 20px; }

    #offline-banner:not(.hidden) {
      position: relative;
      z-index: 20;
      border-bottom: 1px solid #E8D5A2;
      background: linear-gradient(135deg, #FBF3DC, #F8EFD8);
      color: #7A5A14;
    }

    .dark .surface { background: var(--shell-surface); border-color: var(--shell-border); }
    .dark .flash-success { border-color: #315B49; background: #12291F; color: #5FD0A0; }
    .dark .flash-error { border-color: #65363D; background: #3A252B; color: #FF8B97; }
    .dark .logout-button:hover { border-color: var(--shell-debit); background: #3A252B; }
    .dark #offline-banner:not(.hidden) { border-color: #55481F; background: #26200F; color: #E4C878; }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }

    .app-topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      border-bottom: 1px solid var(--shell-border);
      background: var(--shell-surface);
    }
    .app-topbar-inner {
      display: flex;
      width: min(100%, 1280px);
      min-height: 64px;
      align-items: center;
      gap: clamp(12px, 2vw, 28px);
      margin-inline: auto;
      padding: 10px clamp(16px, 2.5vw, 32px);
    }
    .app-brand {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 9px;
      color: var(--shell-text);
      text-decoration: none;
    }
    .app-brand-mark {
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      border-radius: 10px;
      background: var(--shell-primary);
      color: var(--shell-primary-contrast);
    }
    .app-brand-mark svg { width: 21px; height: 21px; }
    .app-brand-mark .material-symbols-outlined { font-size: 20px; }
    .app-brand-name { font-size: 15px; font-weight: 700; white-space: nowrap; }
    .topbar-nav { display: flex; min-width: 0; align-items: center; gap: 3px; margin-inline: auto; overflow-x: auto; scrollbar-width: none; }
    .topbar-nav::-webkit-scrollbar { display: none; }
    .topbar-link {
      display: inline-flex;
      min-height: 40px;
      align-items: center;
      gap: 6px;
      padding: 0 10px;
      border-radius: 8px;
      color: var(--shell-muted);
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      white-space: nowrap;
      transition: background-color 150ms, color 150ms;
    }
    .topbar-link svg { width: 17px; height: 17px; flex: 0 0 auto; }
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
    .topbar-account {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 8px;
      padding-inline-start: 12px;
      border-inline-start: 1px solid var(--shell-border);
    }
    .topbar-account-copy { display: none; gap: 1px; text-align: start; }
    .topbar-account-name { color: var(--shell-text); font-size: 12px; font-weight: 700; white-space: nowrap; }
    .topbar-account-role { color: var(--shell-muted); font-size: 10px; white-space: nowrap; }
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
    .topbar-actions { display: flex; align-items: center; gap: 6px; }
    .topbar-actions .icon-button,
    .topbar-actions .logout-button { width: 36px; min-height: 36px; border-radius: 8px; }
    .topbar-actions .icon-button span,
    .topbar-actions .logout-button span { display: none; }
    .topbar-actions form { margin: 0; }
    .topbar-menu { display: none; position: relative; }
    .topbar-menu > summary {
      display: grid;
      width: 40px;
      height: 40px;
      place-items: center;
      border: 1px solid var(--shell-border);
      border-radius: 8px;
      color: var(--shell-text);
      cursor: pointer;
      list-style: none;
    }
    .topbar-menu > summary::-webkit-details-marker { display: none; }
    .topbar-menu > summary svg { width: 20px; height: 20px; }
    .topbar-menu-list {
      position: absolute;
      z-index: 20;
      inset-inline-start: 0;
      inset-inline-end: auto;
      top: calc(100% + 9px);
      display: none;
      width: min(270px, calc(100vw - 24px));
      max-width: calc(100vw - 24px);
      max-height: min(65vh, 420px);
      gap: 4px;
      overflow-y: auto;
      border: 1px solid var(--shell-border);
      border-radius: 12px;
      background: var(--shell-surface);
      padding: 7px;
      box-shadow: var(--shell-shadow);
    }
    .topbar-menu[open] .topbar-menu-list { display: grid; }
    .topbar-menu-list .topbar-link { min-height: 44px; padding-inline: 10px; font-size: 13px; }

    @media (min-width: 1200px) {
      .topbar-account-copy { display: grid; }
      .topbar-actions .logout-button { width: auto; padding-inline: 10px; }
      .topbar-actions .logout-button span { display: inline; }
    }
    @media (max-width: 1199px) {
      .app-topbar-inner { gap: 12px; }
      .topbar-nav { display: flex; }
      .topbar-menu { display: none; }
      .topbar-account { margin-inline-start: 0; }
    }
    @media (max-width: 1023px) {
      .app-topbar-inner { min-height: 60px; padding-inline: 24px; }
      .topbar-nav { max-width: 45vw; }
      .topbar-link { padding-inline: 8px; font-size: 11px; }
      .app-main { width: 100%; padding: 24px clamp(16px, 3vw, 24px) 48px; }
    }
    @media (max-width: 600px) {
      html { scroll-padding-top: 60px; }
      .app-topbar-inner { min-height: 60px; gap: 9px; padding: 8px 14px; }
      .app-brand { gap: 6px; }
      .app-brand-mark { width: 34px; height: 34px; }
      .app-brand-name { font-size: 13px; }
      .topbar-nav { display: none; }
      .topbar-menu { display: block; margin-inline-start: auto; }
      .topbar-account { gap: 0; padding-inline-start: 0; border-inline-start: 0; }
      .topbar-account-copy { display: none; }
      .topbar-avatar { width: 30px; height: 30px; font-size: 12px; }
      .topbar-actions { gap: 4px; }
      .topbar-actions .icon-button,
      .topbar-actions .logout-button { width: 34px; min-height: 34px; }
      .topbar-menu > summary { width: 36px; height: 36px; }
      .app-main { padding: 16px 14px 36px; }
      .main-page-heading { align-items: flex-start; margin-bottom: 22px; padding-bottom: 12px; }
      .main-page-title { font-size: 20px; }
      .page-description { max-width: 70vw; font-size: 12px; }
      .page-date { padding: 6px 8px; font-size: 10px; }
    }
    @media (max-width: 380px) {
      .app-brand-name { display: none; }
      .topbar-avatar { width: 26px; height: 26px; }
      .topbar-actions { gap: 2px; }
      .topbar-actions .icon-button,
      .topbar-actions .logout-button { width: 30px; min-height: 30px; }
      .topbar-menu > summary { width: 32px; height: 32px; }
    }
  </style>
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/responsive.css?v=2')) ?>">
</head>
<body>
  <div id="offline-banner" class="offline-banner hidden" role="status">الاتصال غير متاح. لم يتم حفظ أي إدخال دون اتصال؛ تحقق من الشبكة قبل إعادة المحاولة.</div>
  <header class="app-topbar">
    <div class="app-topbar-inner">
      <a class="app-brand" href="<?= View::e(Url::to('/')) ?>">
        <span class="app-brand-mark" aria-hidden="true"><span class="material-symbols-outlined">menu_book</span></span>
        <span class="app-brand-name">دفتر الحساب</span>
      </a>

      <nav class="topbar-nav" aria-label="التنقل الرئيسي">
        <?php foreach ($nav as [$path, $label, $icon]): ?>
          <?php $active = $path === '/' ? $currentPath === '/' : ($currentPath === $path || str_starts_with($currentPath, $path . '/')); ?>
          <a class="topbar-link <?= $active ? 'is-active' : '' ?>" href="<?= View::e(Url::to($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <?= $icons[$icon] ?><span><?= View::e($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <details class="topbar-menu">
        <summary aria-label="فتح قائمة التنقل">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </summary>
        <nav class="topbar-menu-list" aria-label="التنقل الرئيسي">
          <?php foreach ($nav as [$path, $label, $icon]): ?>
            <?php $active = $path === '/' ? $currentPath === '/' : ($currentPath === $path || str_starts_with($currentPath, $path . '/')); ?>
            <a class="topbar-link <?= $active ? 'is-active' : '' ?>" href="<?= View::e(Url::to($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
              <?= $icons[$icon] ?><span><?= View::e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      </details>

      <div class="topbar-account">
        <span class="topbar-account-copy">
          <span class="topbar-account-name"><?= View::e($user['name'] ?? '') ?></span>
          <span class="topbar-account-role"><?= $isOwner ? 'مالك المحل' : 'موظف' ?></span>
        </span>
        <span class="topbar-avatar" aria-hidden="true"><?= View::e(mb_substr($user['name'] ?? 'م', 0, 1)) ?></span>
      </div>
      <div class="topbar-actions">
        <button type="button" data-theme-toggle class="icon-button" aria-label="تغيير المظهر" title="تغيير المظهر"><?= $icons['moon'] ?><?= $icons['sun'] ?><span>المظهر</span></button>
        <form action="<?= View::e(Url::to('/logout')) ?>" method="post">
          <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
          <button class="logout-button" type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج"><?= $icons['logout'] ?><span>خروج</span></button>
        </form>
      </div>
    </div>
  </header>
  <div class="app-shell">
    <main class="app-main">
      <header class="main-page-heading">
        <div>
          <nav aria-label="مسار التنقل" class="page-breadcrumb">
            <span>دفتر الحساب</span>
            <span aria-hidden="true">/</span>
            <span><?= View::e($pageTitle ?? '') ?></span>
          </nav>
          <h1 class="main-page-title"><?= View::e($pageTitle ?? '') ?></h1>
          <p class="page-description"><?= View::e($pageDescription) ?></p>
        </div>
        <?php if ($currentPath === '/'): ?>
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