<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

/*
  صفحة تسجيل الدخول — الهوية الزمرددية/الذهبية
  ملاحظة: النصوص التسويقية في اللوحة الجانبية (العنوان والمزايا) قابلة للتعديل
  بحرية من قسم "brand-panel" أدناه دون التأثير على أي منطق برمجي.
*/
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F3F6F4">
  <title><?= View::e($pageTitle) ?> - <?= View::e($GLOBALS['config']['app']['name']) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%230E6B5C'/%3E%3Crect x='19' y='12' width='26' height='40' rx='5' fill='none' stroke='white' stroke-width='3.5'/%3E%3Cline x1='25' y1='21' x2='39' y2='21' stroke='white' stroke-width='3.5' stroke-linecap='round'/%3E%3Ccircle cx='25' cy='32' r='2.4' fill='white'/%3E%3Ccircle cx='32' cy='32' r='2.4' fill='white'/%3E%3Ccircle cx='39' cy='32' r='2.4' fill='white'/%3E%3Ccircle cx='25' cy='41' r='2.4' fill='white'/%3E%3Ccircle cx='32' cy='41' r='2.4' fill='white'/%3E%3Ccircle cx='39' cy='41' r='2.4' fill='%23E3C176'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: light;
      --primary: #0E6B5C;
      --primary-hover: #0A554A;
      --primary-contrast: #FFFFFF;
      --primary-soft: #EDF4F1;
      --ring: rgb(14 107 92 / 16%);
      --btn-shadow: rgb(14 107 92 / 28%);
      --gold: #C29A45;
      --background: #F3F6F4;
      --surface: #FFFFFF;
      --border: #DEE7E2;
      --text: #122621;
      --muted: #5D6F68;
      --debit: #B42333;
      --credit: #1B7F5C;
      --error-soft: #FBEAEC;
      --shadow: rgb(18 38 33 / 10%);
    }

    .dark {
      color-scheme: dark;
      --primary: #56C9AD;
      --primary-hover: #6ED4BC;
      --primary-contrast: #0B1512;
      --primary-soft: #1A2924;
      --ring: rgb(86 201 173 / 20%);
      --btn-shadow: rgb(86 201 173 / 22%);
      --gold: #DDBA6C;
      --background: #0B1412;
      --surface: #121E1A;
      --border: #24352E;
      --text: #E7EFEA;
      --muted: #8CA29A;
      --debit: #FF8B97;
      --credit: #5FD0A0;
      --error-soft: #3A252B;
      --shadow: rgb(0 0 0 / 32%);
    }

    *, *::before, *::after { box-sizing: border-box; }

    html { -webkit-text-size-adjust: 100%; }

    body {
      min-width: 320px;
      margin: 0;
      background: var(--background);
      color: var(--text);
      font-family: 'IBM Plex Sans Arabic', Tajawal, 'Segoe UI', system-ui, sans-serif;
      font-weight: 500;
      font-variant-numeric: tabular-nums;
    }

    ::selection { background: var(--primary); color: var(--primary-contrast); }

    button, input { font: inherit; }

    /* ===== الهيكل العام: لوحة تعريفية + منطقة النموذج ===== */
    .shell {
      display: grid;
      grid-template-columns: minmax(430px, 520px) minmax(0, 1fr);
      min-height: 100vh;
      min-height: 100svh;
    }

    /* ===== اللوحة التعريفية (يمين) ===== */
    .brand-panel {
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 40px;
      overflow: hidden;
      padding: clamp(28px, 4vw, 48px);
      background:
        radial-gradient(480px 340px at 88% -8%, rgb(198 158 74 / 30%), transparent 62%),
        radial-gradient(560px 420px at -12% 112%, rgb(86 201 173 / 24%), transparent 62%),
        linear-gradient(158deg, #0C3B32 4%, #10604F 52%, #093F35 100%);
      color: #FFFFFF;
    }
    .brand-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        repeating-linear-gradient(0deg, rgb(255 255 255 / 5%) 0 1px, transparent 1px 34px),
        repeating-linear-gradient(90deg, rgb(255 255 255 / 4%) 0 1px, transparent 1px 34px);
      -webkit-mask-image: radial-gradient(120% 95% at 55% 38%, #000 28%, transparent 100%);
      mask-image: radial-gradient(120% 95% at 55% 38%, #000 28%, transparent 100%);
      pointer-events: none;
    }
    .brand-watermark {
      position: absolute;
      inset-block-end: -70px;
      inset-inline-start: -55px;
      width: 330px;
      height: 330px;
      color: #FFFFFF;
      opacity: .07;
      transform: rotate(-8deg);
      pointer-events: none;
    }

    .brand-top, .brand-hero, .brand-meta { position: relative; }

    .brand-row { display: flex; align-items: center; gap: 13px; }
    .brand-mark {
      display: grid;
      flex: 0 0 auto;
      width: 50px;
      height: 50px;
      place-items: center;
      border-radius: 15px;
      background: linear-gradient(140deg, #E9CD83, #C29A45);
      color: #12332B;
      box-shadow: 0 10px 26px rgb(0 0 0 / 28%), inset 0 1px 0 rgb(255 255 255 / 45%);
    }
    .brand-mark svg { width: 25px; height: 25px; }
    .brand-titles { display: grid; gap: 2px; }
    .brand-name { font-size: 17px; font-weight: 700; line-height: 1.5; }
    .brand-trader { font-size: 12.5px; color: rgb(255 255 255 / 66%); line-height: 1.6; }

    .brand-hero { display: grid; gap: 20px; max-width: 46ch; }
    .hero-title {
      margin: 0;
      font-size: clamp(24px, 2.4vw, 30px);
      font-weight: 700;
      line-height: 1.7;
      letter-spacing: -.2px;
    }
    .hero-title .hl {
      background: linear-gradient(120deg, #EBCE8C, #C29A45);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .hero-text {
      margin: 0;
      font-size: 14.5px;
      line-height: 1.95;
      color: rgb(255 255 255 / 74%);
    }
    .hero-points {
      display: grid;
      gap: 13px;
      margin: 6px 0 0;
      padding: 0;
      list-style: none;
    }
    .hero-points li { display: flex; align-items: center; gap: 12px; font-size: 14px; color: rgb(255 255 255 / 88%); }
    .point-icon {
      display: grid;
      flex: 0 0 auto;
      width: 37px;
      height: 37px;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 16%);
      border-radius: 11px;
      background: rgb(255 255 255 / 8%);
      color: #E9CD83;
    }
    .point-icon svg { width: 18px; height: 18px; }

    .brand-meta { display: grid; gap: 13px; }
    .meta-line {
      height: 1px;
      margin: 0;
      border: 0;
      background: linear-gradient(90deg, transparent, rgb(255 255 255 / 24%), transparent);
    }
    .meta-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      font-size: 12px;
      color: rgb(255 255 255 / 60%);
    }
    .meta-note { display: inline-flex; align-items: center; gap: 7px; }
    .meta-note svg { width: 14px; height: 14px; }

    /* ===== منطقة النموذج (يسار) ===== */
    .form-panel {
      position: relative;
      display: grid;
      align-content: center;
      align-content: safe center;
      justify-items: center;
      padding: clamp(28px, 5vw, 56px) 24px;
      background:
        radial-gradient(640px 320px at 82% -6%, rgb(14 107 92 / 7%), transparent 60%),
        var(--background);
    }
    .form-inner { width: min(100%, 400px); }

    .mobile-brand { display: none; }

    .form-heading { display: grid; gap: 7px; margin: 0 0 26px; }
    h1 { margin: 0; font-size: 26px; font-weight: 700; line-height: 1.5; }
    .subtitle { margin: 0; color: var(--muted); font-size: 14.5px; line-height: 1.8; }

    .error-message {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin: 0 0 20px;
      padding: 12px 14px;
      border: 1px solid rgb(180 35 51 / 25%);
      border-radius: 12px;
      background: var(--error-soft);
      color: var(--debit);
      font-size: 14px;
      line-height: 1.7;
    }
    .error-message svg { flex: 0 0 auto; width: 18px; height: 18px; margin-top: 3px; }

    .login-form { display: grid; gap: 18px; }
    .field-label { display: grid; gap: 8px; font-size: 13.5px; font-weight: 700; }
    .field-wrap { position: relative; display: flex; align-items: center; }
    .field-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      translate: 0 -50%;
      width: 18px;
      height: 18px;
      color: var(--muted);
      pointer-events: none;
      transition: color 150ms;
    }
    .field-wrap:focus-within .field-icon { color: var(--primary); }
    .field-input {
      display: block;
      width: 100%;
      height: 50px;
      padding: 0 14px 0 46px;
      border: 1px solid var(--border);
      border-radius: 13px;
      outline: none;
      background: var(--primary-soft);
      color: var(--text);
      font-size: 15px;
      font-weight: 500;
      transition: border-color 150ms, box-shadow 150ms, background-color 150ms;
    }
    .field-input[dir="ltr"] { text-align: left; }
    .field-input::placeholder { color: var(--muted); opacity: .75; }
    .field-input:hover { border-color: color-mix(in srgb, var(--primary) 35%, var(--border)); }
    .field-input:focus {
      border-color: var(--primary);
      background: var(--surface);
      box-shadow: 0 0 0 4px var(--ring);
    }
    .field-input:-webkit-autofill {
      -webkit-box-shadow: 0 0 0 40px var(--primary-soft) inset;
      -webkit-text-fill-color: var(--text);
      transition: background-color 9999s ease-out;
    }

    .password-input { padding-right: 52px; }
    .password-toggle {
      position: absolute;
      right: 8px;
      top: 50%;
      translate: 0 -50%;
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      border: 0;
      border-radius: 9px;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      transition: background-color 150ms, color 150ms;
    }
    .password-toggle:hover {
      background: color-mix(in srgb, var(--primary) 10%, transparent);
      color: var(--primary);
    }
    .password-toggle svg { width: 19px; height: 19px; }
    .password-toggle .eye-off { display: none; }
    .password-toggle[aria-pressed="true"] .eye-on { display: none; }
    .password-toggle[aria-pressed="true"] .eye-off { display: block; }

    .caps-hint {
      display: flex;
      align-items: center;
      gap: 7px;
      margin: -4px 0 0;
      color: var(--gold);
      font-size: 12.5px;
      font-weight: 600;
    }
    .caps-hint[hidden] { display: none; }
    .caps-hint svg { width: 15px; height: 15px; flex: 0 0 auto; }

    .submit-button {
      display: flex;
      min-height: 52px;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 4px;
      border: 0;
      border-radius: 13px;
      background: linear-gradient(135deg, var(--primary), var(--primary-hover));
      color: var(--primary-contrast);
      font-size: 15.5px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 12px 26px var(--btn-shadow);
      transition: transform 150ms, box-shadow 150ms, filter 150ms;
    }
    .submit-button:hover { transform: translateY(-1px); filter: brightness(1.06); box-shadow: 0 16px 32px var(--btn-shadow); }
    .submit-button:active { transform: translateY(0); }
    .submit-button:disabled { opacity: .75; cursor: default; transform: none; }
    .submit-button svg { width: 19px; height: 19px; transition: translate 150ms; }
    .submit-button:hover svg { translate: -3px 0; }

    .security-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin: 22px 0 0;
      color: var(--muted);
      font-size: 12.5px;
      line-height: 1.8;
      text-align: center;
    }
    .security-note svg { flex: 0 0 auto; width: 16px; height: 16px; color: var(--gold); }

    .panel-footer { display: none; margin: 26px 0 0; color: var(--muted); font-size: 12px; text-align: center; opacity: .85; }

    /* ===== زر تبديل المظهر ===== */
    .theme-toggle {
      position: fixed;
      inset-block-start: 20px;
      inset-inline-end: 20px;
      z-index: 5;
      display: grid;
      width: 44px;
      height: 44px;
      place-items: center;
      border: 1px solid var(--border);
      border-radius: 13px;
      background: color-mix(in srgb, var(--surface) 85%, transparent);
      -webkit-backdrop-filter: blur(8px);
      backdrop-filter: blur(8px);
      color: var(--muted);
      cursor: pointer;
      box-shadow: 0 6px 18px var(--shadow);
      transition: border-color 150ms, color 150ms;
    }
    .theme-toggle:hover { border-color: var(--primary); color: var(--primary); }
    .theme-toggle svg { width: 20px; height: 20px; }
    .theme-toggle .icon-sun { display: none; }
    .dark .theme-toggle .icon-sun { display: block; }
    .dark .theme-toggle .icon-moon { display: none; }

    :focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }

    /* ===== حركة دخول ناعمة ===== */
    @media (prefers-reduced-motion: no-preference) {
      .rise { animation: rise .6s cubic-bezier(.21, .61, .35, 1) backwards; }
      .d1 { animation-delay: .04s; }
      .d2 { animation-delay: .1s; }
      .d3 { animation-delay: .16s; }
      .d4 { animation-delay: .22s; }
      .d5 { animation-delay: .28s; }
      @keyframes rise {
        from { opacity: 0; transform: translateY(14px); }
      }
    }

    /* ===== الشاشات المتوسطة والصغيرة ===== */
    @media (max-width: 919px) {
      .shell { grid-template-columns: 1fr; }
      .brand-panel { display: none; }
      .form-panel {
        align-content: start;
        padding: 66px 18px 36px;
      }
      .mobile-brand {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        overflow: hidden;
        margin: 0 0 28px;
        padding: 15px 17px;
        border-radius: 17px;
        background:
          radial-gradient(240px 130px at 88% -30%, rgb(198 158 74 / 30%), transparent 62%),
          linear-gradient(140deg, #0C3B32, #10604F 58%, #0A463B);
        color: #FFFFFF;
        box-shadow: 0 14px 30px rgb(9 63 53 / 22%);
      }
      .mobile-brand::after {
        content: "";
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(90deg, rgb(255 255 255 / 5%) 0 1px, transparent 1px 30px);
        pointer-events: none;
      }
      .mobile-brand > * { position: relative; z-index: 1; }
      .mobile-brand .brand-mark { width: 44px; height: 44px; border-radius: 13px; }
      .mobile-brand .brand-mark svg { width: 22px; height: 22px; }
      .mobile-brand .brand-name { font-size: 15px; }
      .mobile-brand .brand-trader { font-size: 12px; }
      .panel-footer { display: block; }
    }

    @media (max-width: 480px) {
      .theme-toggle { inset-block-start: 12px; inset-inline-end: 12px; width: 40px; height: 40px; }
      h1 { font-size: 23px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
  </style>
  <script>
    try {
      if (localStorage.getItem('ledger-theme') === 'dark') {
        document.documentElement.classList.add('dark');
      }
    } catch (error) {
      console.warn('تعذر استعادة تفضيل المظهر.', error);
    }
  </script>
</head>
<body>
  <button type="button" class="theme-toggle" data-theme-toggle aria-label="تغيير المظهر" aria-pressed="false">
    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
    </svg>
    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="4"/>
      <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
    </svg>
  </button>

  <div class="shell">
    <!-- اللوحة التعريفية الجانبية -->
    <aside class="brand-panel" aria-label="عن التطبيق">
      <svg class="brand-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="16" height="20" x="4" y="2" rx="2"/>
        <line x1="8" x2="16" y1="6" y2="6"/>
        <line x1="16" x2="16" y1="14" y2="18"/>
        <path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/>
        <path d="M12 14h.01"/><path d="M8 14h.01"/>
        <path d="M12 18h.01"/><path d="M8 18h.01"/>
      </svg>

      <div class="brand-top rise d1">
        <div class="brand-row">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect width="16" height="20" x="4" y="2" rx="2"/>
              <line x1="8" x2="16" y1="6" y2="6"/>
              <line x1="16" x2="16" y1="14" y2="18"/>
              <path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/>
              <path d="M12 14h.01"/><path d="M8 14h.01"/>
              <path d="M12 18h.01"/><path d="M8 18h.01"/>
            </svg>
          </span>
          <span class="brand-titles">
            <strong class="brand-name"><?= View::e($GLOBALS['config']['app']['name']) ?></strong>
            <span class="brand-trader"><?= View::e($GLOBALS['config']['app']['trader_name']) ?></span>
          </span>
        </div>
      </div>

      <div class="brand-hero">
        <p class="hero-title rise d2">سجّل حركاتك وتابع أرصدتك <span class="hl">بثقة ووضوح</span>.</p>
        <p class="hero-text rise d3">لوحة دخول آمنة إلى دفاتر مؤسستك؛ قيودك وأرصدتك وتقاريرك في مكان واحد.</p>
        <ul class="hero-points">
          <li class="rise d3">
            <span class="point-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/>
                <path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>
              </svg>
            </span>
            <span>سجل واضح لكل حركة مالية</span>
          </li>
          <li class="rise d4">
            <span class="point-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
              </svg>
            </span>
            <span>واجهة عربية سريعة وسهلة الاستخدام</span>
          </li>
          <li class="rise d5">
            <span class="point-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </span>
            <span>دخول آمن وحماية لبياناتك</span>
          </li>
        </ul>
      </div>

      <div class="brand-meta rise d5">
        <hr class="meta-line">
        <div class="meta-row">
          <span>© <?= View::e($GLOBALS['config']['app']['name']) ?></span>
          <span class="meta-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            <span>جلسة دخول آمنة</span>
          </span>
        </div>
      </div>
    </aside>

    <!-- منطقة نموذج الدخول -->
    <main class="form-panel">
      <div class="form-inner">
        <header class="mobile-brand rise d1">
          <span class="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect width="16" height="20" x="4" y="2" rx="2"/>
              <line x1="8" x2="16" y1="6" y2="6"/>
              <line x1="16" x2="16" y1="14" y2="18"/>
              <path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/>
              <path d="M12 14h.01"/><path d="M8 14h.01"/>
              <path d="M12 18h.01"/><path d="M8 18h.01"/>
            </svg>
          </span>
          <span class="brand-titles">
            <strong class="brand-name"><?= View::e($GLOBALS['config']['app']['name']) ?></strong>
            <span class="brand-trader"><?= View::e($GLOBALS['config']['app']['trader_name']) ?></span>
          </span>
        </header>

        <section aria-labelledby="login-title">
          <div class="form-heading rise d2">
            <h1 id="login-title">تسجيل الدخول</h1>
            <p class="subtitle">أدخل بيانات حسابك للمتابعة.</p>
          </div>

          <?php if (!empty($error)): ?>
            <p class="error-message" role="alert">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4"/>
                <path d="M12 16h.01"/>
              </svg>
              <span><?= View::e($error) ?></span>
            </p>
          <?php endif; ?>

          <form class="login-form" method="post" action="<?= View::e(Url::to('/login')) ?>">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">

            <label class="field-label rise d3" for="username">اسم المستخدم
              <span class="field-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="8" r="3.5"/>
                  <path d="M5 20a7 7 0 0 1 14 0"/>
                </svg>
                <input
                  class="field-input"
                  id="username"
                  name="username"
                  type="text"
                  dir="ltr"
                  autocomplete="username"
                  autocapitalize="none"
                  spellcheck="false"
                  required
                  maxlength="50"
                  autofocus
                  value="<?= View::e($old['username'] ?? '') ?>"
                  placeholder="اسم المستخدم">
              </span>
            </label>

            <label class="field-label rise d4" for="password">كلمة المرور
              <span class="field-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3.5" y="10.5" width="17" height="10" rx="2"/>
                  <path d="M7 10V7a5 5 0 0 1 10 0v3"/>
                </svg>
                <input
                  class="field-input password-input"
                  id="password"
                  name="password"
                  type="password"
                  dir="ltr"
                  autocomplete="current-password"
                  required
                  placeholder="كلمة المرور">
                <button type="button" class="password-toggle" data-password-toggle aria-label="إظهار كلمة المرور" aria-pressed="false">
                  <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                    <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.5 10.5 0 0 0 4.1-.8"/>
                  </svg>
                </button>
              </span>
              <span class="caps-hint" data-caps-hint hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <path d="M12 9v4"/>
                  <path d="M12 17h.01"/>
                </svg>
                <span>تنبيه: مفتاح Caps Lock مُفعّل.</span>
              </span>
            </label>

            <button class="submit-button rise d5" type="submit">
              <span>دخول</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5"/>
                <path d="m12 19-7-7 7-7"/>
              </svg>
            </button>
          </form>

          <p class="security-note rise d5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            <span>دخول آمن وسجل واضح لكل حركة.</span>
          </p>

          <p class="panel-footer">© <?= View::e($GLOBALS['config']['app']['name']) ?></p>
        </section>
      </div>
    </main>
  </div>

  <script>
    (() => {
      const root = document.documentElement;
      const themeButton = document.querySelector('[data-theme-toggle]');
      const themeColor = document.querySelector('meta[name="theme-color"]');
      const password = document.querySelector('#password');
      const passwordButton = document.querySelector('[data-password-toggle]');
      const capsHint = document.querySelector('[data-caps-hint]');
      const form = document.querySelector('.login-form');
      const submitButton = document.querySelector('.submit-button');
      const submitLabel = submitButton ? submitButton.querySelector('span') : null;

      if (themeButton) {
        themeButton.setAttribute('aria-pressed', String(root.classList.contains('dark')));
        themeButton.addEventListener('click', () => {
          const isDark = root.classList.toggle('dark');
          themeButton.setAttribute('aria-pressed', String(isDark));
          if (themeColor) themeColor.setAttribute('content', isDark ? '#0B1412' : '#F3F6F4');
          try {
            localStorage.setItem('ledger-theme', isDark ? 'dark' : 'light');
          } catch (error) {
            console.warn('تعذر حفظ تفضيل المظهر.', error);
          }
        });
      }

      if (password && passwordButton) {
        passwordButton.addEventListener('click', () => {
          const isVisible = password.type === 'password';
          password.type = isVisible ? 'text' : 'password';
          passwordButton.setAttribute('aria-pressed', String(isVisible));
          passwordButton.setAttribute('aria-label', isVisible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
          password.focus({ preventScroll: true });
        });

        if (capsHint) {
          const syncCaps = (event) => {
            capsHint.hidden = !(typeof event.getModifierState === 'function' && event.getModifierState('CapsLock'));
          };
          password.addEventListener('keydown', syncCaps);
          password.addEventListener('keyup', syncCaps);
          password.addEventListener('blur', () => { capsHint.hidden = true; });
        }
      }

      if (form && submitButton) {
        form.addEventListener('submit', () => {
          submitButton.disabled = true;
          if (submitLabel) submitLabel.textContent = 'جارٍ الدخول…';
        });
      }
    })();
  </script>
</body>
</html>