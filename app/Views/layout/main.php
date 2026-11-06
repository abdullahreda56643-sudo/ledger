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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#F3F6F4">
  <script>
    /* تطبيق المظهر المحفوظ قبل الرسم لتجنّب وميض الوضع الداكن */
    (function () {
      try {
        if (localStorage.getItem('ledger-theme') === 'dark') {
          document.documentElement.classList.add('dark');
          var meta = document.querySelector('meta[name="theme-color"]');
          if (meta) { meta.setAttribute('content', '#0B1412'); }
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
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: { extend: { colors: {
        'ledger-ink': '#122621', 'ledger-red': '#B42333', 'ledger-green': '#1B7F5C',
        'ledger-bg': '#F3F6F4', 'ledger-border': '#DEE7E2', 'ledger-muted': '#5D6F68',
        'ledger-emerald': '#0E6B5C', 'ledger-gold': '#C29A45'
      }, fontFamily: { sans: ['IBM Plex Sans Arabic', 'Tajawal', 'sans-serif'] }, borderRadius: { card: '20px' } } }
    };
  </script>
  <?php if ($currentPath === '/'): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/operation.css')) ?>">
  <?php endif; ?>
  <?php if (str_starts_with($currentPath, '/parties')): ?>
    <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/parties.css?v=2')) ?>">
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
  <link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/print.css')) ?>">
  <script defer src="<?= View::e(Url::to('/assets/js/app.js?v=2')) ?>"></script>
  <style>
    /* ==========================================================
       هيكل التطبيق — الهوية الزمرددية/الذهبية
       أسماء متغيرات --shell-* محفوظة كما هي لتبقى ملفات التنسيق
       الأخرى (parties/reports/users/audit) متوافقة دون تعديل.
       ========================================================== */
    :root {
      color-scheme: light;
      --shell-primary: #0E6B5C;
      --shell-primary-hover: #0A554A;
      --shell-primary-contrast: #FFFFFF;
      --shell-primary-soft: #EDF4F1;
      --shell-gold: #C29A45;
      --shell-gold-ink: #8A6B2B;
      --shell-gold-soft: rgb(194 154 69 / 12%);
      --shell-background: #F3F6F4;
      --shell-surface: #FFFFFF;
      --shell-surface-2: #F7FAF8;
      --shell-border: #DEE7E2;
      --shell-text: #122621;
      --shell-muted: #5D6F68;
      --shell-credit: #1B7F5C;
      --shell-debit: #B42333;
      --shell-ring: rgb(14 107 92 / 16%);
      --shell-shadow: 0 12px 32px rgb(18 38 33 / 8%);
    }

    .dark {
      color-scheme: dark;
      --shell-primary: #56C9AD;
      --shell-primary-hover: #6ED4BC;
      --shell-primary-contrast: #0B1512;
      --shell-primary-soft: #1A2924;
      --shell-gold: #DDBA6C;
      --shell-gold-ink: #E3C176;
      --shell-gold-soft: rgb(221 186 108 / 12%);
      --shell-background: #0B1412;
      --shell-surface: #121E1A;
      --shell-surface-2: #0F1915;
      --shell-border: #24352E;
      --shell-text: #E7EFEA;
      --shell-muted: #8CA29A;
      --shell-credit: #5FD0A0;
      --shell-debit: #FF8B97;
      --shell-ring: rgb(86 201 173 / 20%);
      --shell-shadow: 0 12px 32px rgb(0 0 0 / 30%);
    }

    html { min-width: 320px; font-family: 'IBM Plex Sans Arabic', 'Tajawal', ui-sans-serif, system-ui, sans-serif; }
    body { min-height: 100vh; background: var(--shell-background); color: var(--shell-text); font-size: 16px; font-weight: 500; font-variant-numeric: tabular-nums; }
    ::selection { background: rgb(14 107 92 / 22%); }
    .dark ::selection { background: rgb(86 201 173 / 28%); }
    .text-xs { font-size: .8125rem; line-height: 1.45; }
    .text-sm { font-size: .9375rem; line-height: 1.55; }
    .text-base { font-size: 1rem; line-height: 1.6; }
    .text-lg { font-size: 1.1875rem; line-height: 1.5; }
    .text-xl { font-size: 1.375rem; line-height: 1.4; }
    .text-2xl { font-size: 1.5625rem; line-height: 1.35; }
    .hidden { display: none !important; }
    *, *::before, *::after { box-sizing: border-box; }
    :focus-visible { outline: 3px solid var(--shell-primary); outline-offset: 3px; }

    .app-shell { display: grid; min-height: 100vh; grid-template-columns: 276px minmax(0, 1fr); }

    /* ===== الشريط الجانبي — لوحة الهوية الزمرددية ===== */
    .app-sidebar {
      position: sticky;
      top: 0;
      display: flex;
      height: 100vh;
      height: 100dvh;
      min-height: 0;
      flex-direction: column;
      overflow-y: auto;
      overscroll-behavior: contain;
      border-inline-end: 1px solid rgb(6 34 29 / 45%);
      background:
        radial-gradient(360px 230px at 100% -8%, rgb(194 154 69 / 16%), transparent 62%),
        radial-gradient(420px 260px at -25% 110%, rgb(255 255 255 / 6%), transparent 58%),
        linear-gradient(170deg, #0F4A3E 0%, #0C3B32 55%, #09302A 100%);
      padding: 26px 18px 18px;
      color: #E7EFEA;
      scrollbar-width: thin;
      scrollbar-color: rgb(255 255 255 / 22%) transparent;
    }
    .app-sidebar :focus-visible { outline-color: #E9CD83; }

    .brand-link { display: flex; min-width: 0; align-items: center; gap: 12px; padding: 0 8px; color: #FFFFFF; text-decoration: none; }
    .brand-mark {
      display: grid;
      width: 46px;
      height: 46px;
      flex: 0 0 auto;
      place-items: center;
      border-radius: 14px;
      background: linear-gradient(140deg, #E9CD83, #C29A45);
      color: #12332B;
      box-shadow: inset 0 1px 0 rgb(255 255 255 / 45%), 0 10px 22px rgb(194 154 69 / 32%);
    }
    .brand-mark svg { width: 25px; height: 25px; }
    .brand-copy { display: grid; min-width: 0; gap: 2px; }
    .brand-name { overflow: hidden; font-size: 17px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .brand-caption { color: rgb(233 205 131 / 85%); font-size: 12.5px; }

    .sidebar-divider { height: 1px; margin: 24px 4px 20px; background: linear-gradient(90deg, transparent, rgb(255 255 255 / 16%), transparent); }

    .nav-caption { margin: 0 10px 10px; color: rgb(231 239 234 / 52%); font-size: 12px; font-weight: 700; letter-spacing: .2px; }
    .nav-caption-spaced { margin-top: 20px; }

    .sidebar-nav { display: grid; gap: 5px; }
    .sidebar-link {
      position: relative;
      display: flex;
      min-height: 46px;
      align-items: center;
      gap: 12px;
      padding: 0 12px;
      border-radius: 11px;
      color: rgb(231 239 234 / 74%);
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      transition: background-color 150ms, color 150ms, box-shadow 150ms;
    }
    .sidebar-link svg { width: 20px; height: 20px; flex: 0 0 auto; }
    .sidebar-link:hover { background: rgb(255 255 255 / 8%); color: #FFFFFF; }
    .sidebar-link.is-active {
      background: rgb(255 255 255 / 13%);
      color: #FFFFFF;
      font-weight: 700;
      box-shadow: inset 0 1px 0 rgb(255 255 255 / 10%);
    }
    .sidebar-link.is-active::before {
      position: absolute;
      inset-block: 11px;
      inset-inline-start: 0;
      width: 3px;
      border-radius: 3px;
      background: linear-gradient(180deg, #E9CD83, #C29A45);
      content: "";
    }

    .sidebar-spacer { flex: 1 0 28px; }

    .app-sidebar .account-panel {
      display: grid;
      gap: 15px;
      border: 1px solid rgb(255 255 255 / 12%);
      border-radius: 14px;
      background: rgb(255 255 255 / 6%);
      padding: 13px;
      box-shadow: inset 0 1px 0 rgb(255 255 255 / 6%);
    }
    .account-person { display: flex; min-width: 0; align-items: center; gap: 10px; }
    .account-avatar {
      display: grid;
      width: 38px;
      height: 38px;
      flex: 0 0 auto;
      place-items: center;
      border-radius: 50%;
      background: linear-gradient(140deg, rgb(233 205 131 / 32%), rgb(194 154 69 / 20%));
      color: #E9CD83;
      font-size: 16px;
      font-weight: 700;
    }
    .account-copy { display: grid; min-width: 0; gap: 2px; }
    .account-name { overflow: hidden; color: #FFFFFF; font-size: 14px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .account-role { color: rgb(231 239 234 / 60%); font-size: 12px; }
    .account-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .account-actions > form { min-width: 0; }
    .account-actions > form > button { width: 100%; }

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

    /* أزرار لوحة الحساب داخل الشريط الزمردي */
    .app-sidebar .icon-button, .app-sidebar .logout-button {
      border-color: rgb(255 255 255 / 14%);
      background: rgb(255 255 255 / 7%);
      color: rgb(231 239 234 / 82%);
    }
    .app-sidebar .icon-button:hover { border-color: rgb(233 205 131 / 55%); background: rgb(255 255 255 / 11%); color: #E9CD83; }
    .app-sidebar .logout-button { color: #FFA5AE; }
    .app-sidebar .logout-button:hover { border-color: rgb(255 165 174 / 55%); background: rgb(180 35 51 / 24%); color: #FFB9C0; }

    .theme-icon { display: block; width: 18px; height: 18px; }
    .theme-icon-sun { display: none; }
    .dark .theme-icon-moon { display: none; }
    .dark .theme-icon-sun { display: block; }

    .app-main { min-width: 0; width: min(100%, 1500px); margin-inline: auto; padding: 30px clamp(20px, 4vw, 56px) 48px; }

    .page-header { display: flex; min-height: 78px; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
    .page-heading { min-width: 0; }
    .page-kicker { display: flex; align-items: center; gap: 8px; margin: 0 0 4px; color: var(--shell-muted); font-size: 13.5px; font-weight: 600; }
    .page-kicker::before {
      content: "";
      width: 8px;
      height: 8px;
      flex: 0 0 auto;
      border-radius: 3px;
      background: linear-gradient(140deg, #E9CD83, #C29A45);
      box-shadow: 0 2px 8px rgb(194 154 69 / 45%);
    }
    .page-title { margin: 0; color: var(--shell-text); font-size: clamp(23px, 2.2vw, 30px); font-weight: 700; line-height: 1.35; }

    .header-account { display: flex; align-items: center; gap: 10px; }
    .header-account-copy { display: grid; gap: 1px; text-align: left; }
    .header-account-name { color: var(--shell-text); font-size: 14px; font-weight: 700; }
    .header-account-role { color: var(--shell-muted); font-size: 12px; }
    .header-avatar {
      display: grid;
      width: 42px;
      height: 42px;
      place-items: center;
      border: 1px solid color-mix(in srgb, var(--shell-primary) 22%, var(--shell-border));
      border-radius: 50%;
      background: var(--shell-primary-soft);
      color: var(--shell-primary);
      font-size: 15px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgb(14 107 92 / 10%);
    }
    .mobile-header-actions, .mobile-nav { display: none; }

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

    @media (max-width: 1023px) {
      .app-shell { display: block; }
      .app-sidebar { display: none; }
      .app-main { padding: 18px 20px 104px; }
      .page-header { min-height: 60px; margin-bottom: 20px; }
      .page-kicker { display: none; }
      .mobile-header-actions { display: flex; align-items: center; gap: 8px; }
      .mobile-header-actions .icon-button, .mobile-header-actions .logout-button { width: 42px; min-height: 42px; padding: 0; }
      .mobile-header-actions .logout-button span { display: none; }
      .header-account { display: none; }
      .mobile-nav {
        position: fixed;
        z-index: 15;
        inset-inline: 0;
        bottom: 0;
        display: grid;
        grid-template-columns: repeat(var(--mobile-nav-count), minmax(0, 1fr));
        gap: 4px;
        border-top: 1px solid var(--shell-border);
        background: color-mix(in srgb, var(--shell-surface) 94%, transparent);
        padding: 7px 8px max(7px, env(safe-area-inset-bottom));
        backdrop-filter: blur(16px);
      }
      .mobile-nav-link {
        display: flex;
        min-width: 0;
        min-height: 56px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        border-radius: 10px;
        color: var(--shell-muted);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
      }
      .mobile-nav-link svg { width: 20px; height: 20px; }
      .mobile-nav-link.is-active { background: var(--shell-primary-soft); color: var(--shell-primary); font-weight: 700; }
      .mobile-more { position: relative; min-width: 0; }
      .mobile-more > summary { cursor: pointer; list-style: none; }
      .mobile-more > summary::-webkit-details-marker { display: none; }
      .mobile-more > summary::after {
        position: absolute;
        top: 5px;
        inset-inline-end: calc(50% - 14px);
        width: 5px;
        height: 5px;
        border-top: 1.5px solid currentColor;
        border-left: 1.5px solid currentColor;
        content: "";
        transform: rotate(45deg);
      }
      .mobile-more > summary.is-active { background: var(--shell-primary-soft); color: var(--shell-primary); font-weight: 700; }
      .mobile-more-menu {
        position: absolute;
        inset-inline-end: 0;
        bottom: calc(100% + 10px);
        display: none;
        width: min(250px, calc(100vw - 24px));
        max-height: min(55vh, 360px);
        gap: 5px;
        overflow-y: auto;
        overscroll-behavior: contain;
        border: 1px solid var(--shell-border);
        border-radius: 14px;
        background: var(--shell-surface);
        padding: 8px;
        box-shadow: var(--shell-shadow);
      }
      .mobile-more[open] .mobile-more-menu { display: grid; }
      .mobile-more-link {
        display: flex;
        min-height: 46px;
        align-items: center;
        gap: 10px;
        border-radius: 10px;
        padding: 0 11px;
        color: var(--shell-muted);
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
      }
      .mobile-more-link svg { width: 19px; height: 19px; }
      .mobile-more-link:hover, .mobile-more-link.is-active { background: var(--shell-primary-soft); color: var(--shell-primary); }
    }
    @media (max-width: 480px) {
      .app-main { padding-inline: 14px; }
      .page-header { gap: 8px; }
      .page-title { font-size: 22px; }
      .mobile-nav-link { font-size: 11px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
  </style>
</head>
<body>
  <div id="offline-banner" class="hidden px-4 py-3 text-center text-sm" role="status">الاتصال غير متاح. لم يتم حفظ أي إدخال دون اتصال؛ تحقق من الشبكة قبل إعادة المحاولة.</div>
  <div class="app-shell">
    <aside class="app-sidebar">
      <a class="brand-link" href="<?= View::e(Url::to('/')) ?>">
        <span class="brand-mark" aria-hidden="true"><?= $icons['statement'] ?></span>
        <span class="brand-copy">
          <span class="brand-name"><?= View::e($GLOBALS['config']['app']['trader_name']) ?></span>
          <span class="brand-caption">دفتر الحساب</span>
        </span>
      </a>
      <div class="sidebar-divider"></div>
      <p class="nav-caption">القائمة الرئيسية</p>
      <nav class="sidebar-nav" aria-label="القائمة الرئيسية">
        <?php foreach ($nav as [$path, $label, $icon]): ?>
          <?php if ($path === '/users'): ?><p class="nav-caption nav-caption-spaced">الإدارة</p><?php endif; ?>
          <?php $active = $path === '/' ? $currentPath === '/' : ($currentPath === $path || str_starts_with($currentPath, $path . '/')); ?>
          <a class="sidebar-link <?= $active ? 'is-active' : '' ?>" href="<?= View::e(Url::to($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <?= $icons[$icon] ?><span><?= View::e($label) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-spacer"></div>
      <section class="account-panel" aria-label="حساب المستخدم">
        <div class="account-person">
          <span class="account-avatar" aria-hidden="true"><?= View::e(mb_substr($user['name'] ?? 'م', 0, 1)) ?></span>
          <span class="account-copy">
            <span class="account-name"><?= View::e($user['name'] ?? '') ?></span>
            <span class="account-role"><?= $isOwner ? 'مالك النظام' : 'مستخدم' ?></span>
          </span>
        </div>
        <div class="account-actions">
          <button type="button" data-theme-toggle class="icon-button" aria-label="تغيير المظهر" title="تغيير المظهر"><?= $icons['moon'] ?><?= $icons['sun'] ?><span>المظهر</span></button>
          <form action="<?= View::e(Url::to('/logout')) ?>" method="post">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button class="logout-button" type="submit"><?= $icons['logout'] ?><span>خروج</span></button>
          </form>
        </div>
      </section>
    </aside>
    <main class="app-main">
      <header class="page-header">
        <div class="page-heading">
          <p class="page-kicker"><?= View::e($GLOBALS['config']['app']['trader_name']) ?></p>
          <h1 class="page-title"><?= View::e($pageTitle ?? '') ?></h1>
        </div>
        <div class="header-account">
          <span class="header-account-copy">
            <span class="header-account-name"><?= View::e($user['name'] ?? '') ?></span>
            <span class="header-account-role"><?= $isOwner ? 'مالك النظام' : 'مستخدم' ?></span>
          </span>
          <span class="header-avatar" aria-hidden="true"><?= View::e(mb_substr($user['name'] ?? 'م', 0, 1)) ?></span>
        </div>
        <div class="mobile-header-actions">
          <button type="button" data-theme-toggle class="icon-button" aria-label="تغيير المظهر" title="تغيير المظهر"><?= $icons['moon'] ?><?= $icons['sun'] ?></button>
          <form action="<?= View::e(Url::to('/logout')) ?>" method="post">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <button class="logout-button" type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج"><?= $icons['logout'] ?><span>خروج</span></button>
          </form>
        </div>
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
  <nav class="mobile-nav" style="--mobile-nav-count: <?= $isOwner ? '5' : '3' ?>" aria-label="التنقل الرئيسي">
    <a class="mobile-nav-link <?= $currentPath === '/' ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/')) ?>" <?= $currentPath === '/' ? 'aria-current="page"' : '' ?>>
      <?= $icons['home'] ?><span>التشغيل</span>
    </a>
    <a class="mobile-nav-link <?= str_starts_with($currentPath, '/parties') ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/parties')) ?>" <?= str_starts_with($currentPath, '/parties') ? 'aria-current="page"' : '' ?>>
      <?= $icons['users'] ?><span>الأطراف</span>
    </a>
    <?php if ($isOwner): ?>
      <a class="mobile-nav-link <?= str_starts_with($currentPath, '/reports/debtors') ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/reports/debtors')) ?>" <?= str_starts_with($currentPath, '/reports/debtors') ? 'aria-current="page"' : '' ?>>
        <?= $icons['chart'] ?><span>التقارير</span>
      </a>
    <?php endif; ?>
    <?php $statementActive = str_starts_with($currentPath, '/statement'); ?>
    <a class="mobile-nav-link <?= $statementActive ? 'is-active' : '' ?>" href="<?= View::e($statementActive ? Url::to($currentPath) : Url::to('/parties')) ?>" <?= $statementActive ? 'aria-current="page"' : '' ?>>
      <?= $icons['statement'] ?><span>كشف حساب</span>
    </a>
    <?php if ($isOwner): ?>
      <?php $adminSectionActive = str_starts_with($currentPath, '/users') || str_starts_with($currentPath, '/audit'); ?>
      <details class="mobile-more">
        <summary class="mobile-nav-link <?= $adminSectionActive ? 'is-active' : '' ?>" aria-label="المزيد من خيارات الإدارة">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
          <span>المزيد</span>
        </summary>
        <div class="mobile-more-menu">
          <a class="mobile-more-link <?= str_starts_with($currentPath, '/users') ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/users')) ?>" <?= str_starts_with($currentPath, '/users') ? 'aria-current="page"' : '' ?>>
            <?= $icons['settings'] ?><span>إدارة المستخدمين</span>
          </a>
          <a class="mobile-more-link <?= str_starts_with($currentPath, '/audit') ? 'is-active' : '' ?>" href="<?= View::e(Url::to('/audit')) ?>" <?= str_starts_with($currentPath, '/audit') ? 'aria-current="page"' : '' ?>>
            <?= $icons['activity'] ?><span>سجل التدقيق</span>
          </a>
        </div>
      </details>
    <?php endif; ?>
  </nav>
</body>
</html>