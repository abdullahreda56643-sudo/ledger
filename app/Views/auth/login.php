<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F4F6F8">
  <title><?= View::e($pageTitle) ?> - <?= View::e($GLOBALS['config']['app']['name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: light;
      --primary: #1E3A5F;
      --primary-hover: #264B73;
      --primary-soft: #EEF3F9;
      --background: #F4F6F8;
      --surface: #FFFFFF;
      --border: #E2E7EC;
      --text: #17212B;
      --muted: #5B6874;
      --debit: #B42333;
      --credit: #1B7F5C;
      --error-soft: #FBEAEC;
      --shadow: rgb(23 33 43 / 9%);
    }

    .dark {
      color-scheme: dark;
      --primary: #8DB4E8;
      --primary-hover: #A7C6EF;
      --primary-soft: #202D3D;
      --background: #0F1620;
      --surface: #171F2B;
      --border: #263140;
      --text: #E6ECF2;
      --muted: #93A1B0;
      --debit: #FF8B97;
      --credit: #5FD0A0;
      --error-soft: #3A252B;
      --shadow: rgb(0 0 0 / 24%);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      display: grid;
      min-width: 320px;
      min-height: 100vh;
      min-height: 100svh;
      place-items: center;
      margin: 0;
      padding: max(24px, env(safe-area-inset-top)) 20px max(24px, env(safe-area-inset-bottom));
      background: var(--background);
      color: var(--text);
      font-family: Tajawal, sans-serif;
      font-weight: 500;
      font-variant-numeric: tabular-nums;
    }

    button, input { font: inherit; }

    .theme-toggle {
      position: fixed;
      inset-block-start: 22px;
      inset-inline-end: 22px;
      display: grid;
      width: 42px;
      height: 42px;
      place-items: center;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--surface);
      color: var(--muted);
      cursor: pointer;
      transition: border-color 150ms, color 150ms, background-color 150ms;
    }
    .theme-toggle:hover { border-color: var(--primary); color: var(--primary); }
    .theme-toggle svg { width: 19px; height: 19px; }

    .login-frame {
      position: relative;
      width: min(100%, 430px);
      overflow: hidden;
      border: 1px solid var(--border);
      border-radius: 20px;
      background: var(--surface);
      box-shadow: 0 18px 48px var(--shadow);
    }
    .frame-top { display: block; width: 100%; height: 116px; background: var(--primary); }
    .avatar {
      position: absolute;
      z-index: 1;
      inset-block-start: 65px;
      inset-inline-start: 50%;
      display: grid;
      width: 84px;
      height: 84px;
      place-items: center;
      transform: translateX(50%);
      border: 5px solid var(--surface);
      border-radius: 50%;
      background: var(--primary);
      color: #FFFFFF;
      box-shadow: 0 5px 16px var(--shadow);
    }
    .avatar svg { width: 39px; height: 39px; }
    .dark .avatar { color: #17212B; }

    .login-content { position: relative; z-index: 1; padding: 28px 42px 25px; }
    .brand { margin: 7px 0 25px; text-align: center; }
    .brand-name { display: block; font-size: 16px; font-weight: 800; line-height: 1.5; }
    .brand-caption { display: block; margin-top: 2px; color: var(--muted); font-size: 13px; }
    .form-heading { margin-bottom: 22px; text-align: center; }
    h1 { margin: 0; font-size: 24px; font-weight: 800; line-height: 1.5; }
    .subtitle { margin: 5px 0 0; color: var(--muted); font-size: 14px; }

    .error-message {
      margin: 0 0 18px;
      padding: 10px 12px;
      border: 1px solid var(--error-soft);
      border-radius: 8px;
      background: var(--error-soft);
      color: var(--debit);
      font-size: 14px;
      line-height: 1.7;
    }

    .login-form { display: grid; gap: 16px; }
    .field-label { display: grid; gap: 7px; font-size: 14px; font-weight: 700; }
    .field-wrap { position: relative; display: flex; align-items: center; }
    .field-input {
      display: block;
      width: 100%;
      height: 46px;
      padding: 0 13px;
      border: 1px solid var(--border);
      border-radius: 8px;
      outline: none;
      background: var(--primary-soft);
      color: var(--text);
      font-size: 15px;
      font-weight: 400;
      transition: border-color 150ms, box-shadow 150ms, background-color 150ms;
    }
    .field-input[dir="ltr"] { text-align: left; }
    .field-input::placeholder { color: var(--muted); opacity: .8; }
    .field-input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px var(--primary); }
    .password-input { padding-inline-start: 60px; }
    .password-toggle {
      position: absolute;
      inset-inline-start: 7px;
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      border: 0;
      border-radius: 6px;
      background: var(--surface);
      color: var(--primary);
      cursor: pointer;
    }
    .password-toggle:hover { background: var(--background); }
    .password-toggle svg { width: 19px; height: 19px; }
    .password-toggle .eye-off { display: none; }
    .password-toggle[aria-pressed="true"] .eye-on { display: none; }
    .password-toggle[aria-pressed="true"] .eye-off { display: block; }

    .submit-button {
      display: flex;
      min-height: 48px;
      align-items: center;
      justify-content: center;
      gap: 9px;
      margin-top: 3px;
      border: 0;
      border-radius: 8px;
      background: var(--primary);
      color: #FFFFFF;
      font-size: 15px;
      font-weight: 800;
      cursor: pointer;
      transition: background-color 150ms, transform 150ms;
    }
    .dark .submit-button { color: #17212B; }
    .submit-button:hover { background: var(--primary-hover); }
    .submit-button:active { transform: translateY(1px); }
    .submit-button svg { width: 18px; height: 18px; }

    .security-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin: 19px 0 0;
      color: var(--muted);
      font-size: 12px;
      line-height: 1.8;
      text-align: center;
    }
    .security-note svg { width: 16px; height: 16px; flex: 0 0 auto; color: var(--primary); }

    :focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }

    @media (max-width: 480px) {
      .theme-toggle { inset-block-start: 12px; inset-inline-end: 12px; }
      .login-frame { width: min(100%, 390px); }
      .login-content { padding-inline: 26px; }
      .frame-top { height: 108px; }
      .avatar { inset-block-start: 58px; }
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
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="4"/>
      <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
    </svg>
  </button>

  <main class="login-frame">
    <div class="frame-top" aria-hidden="true"></div>
    <div class="avatar" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="3.5"/>
        <path d="M5 20a7 7 0 0 1 14 0"/>
      </svg>
    </div>

    <div class="login-content">
      <header class="brand">
        <strong class="brand-name"><?= View::e($GLOBALS['config']['app']['name']) ?></strong>
        <span class="brand-caption"><?= View::e($GLOBALS['config']['app']['trader_name']) ?></span>
      </header>

      <section aria-labelledby="login-title">
        <div class="form-heading">
          <h1 id="login-title">تسجيل الدخول</h1>
          <p class="subtitle">أدخل بيانات حسابك للمتابعة.</p>
        </div>

        <?php if (!empty($error)): ?>
          <p class="error-message" role="alert"><?= View::e($error) ?></p>
        <?php endif; ?>

        <form class="login-form" method="post" action="<?= View::e(Url::to('/login')) ?>">
          <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">

          <label class="field-label" for="username">اسم المستخدم
            <span class="field-wrap">
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

          <label class="field-label" for="password">كلمة المرور
            <span class="field-wrap">
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
          </label>

          <button class="submit-button" type="submit">
            <span>دخول</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
          </button>
        </form>

        <p class="security-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4"/></svg>
          <span>دخول آمن وسجل واضح لكل حركة.</span>
        </p>
      </section>
    </div>
  </main>

  <script>
    (() => {
      const root = document.documentElement;
      const themeButton = document.querySelector('[data-theme-toggle]');
      const password = document.querySelector('#password');
      const passwordButton = document.querySelector('[data-password-toggle]');

      themeButton.setAttribute('aria-pressed', String(root.classList.contains('dark')));
      themeButton.addEventListener('click', () => {
        const isDark = root.classList.toggle('dark');
        themeButton.setAttribute('aria-pressed', String(isDark));
        document.querySelector('meta[name="theme-color"]').setAttribute('content', isDark ? '#0F1620' : '#F4F6F8');
        try {
          localStorage.setItem('ledger-theme', isDark ? 'dark' : 'light');
        } catch (error) {
          console.warn('تعذر حفظ تفضيل المظهر.', error);
        }
      });

      passwordButton.addEventListener('click', () => {
        const isVisible = password.type === 'password';
        password.type = isVisible ? 'text' : 'password';
        passwordButton.setAttribute('aria-pressed', String(isVisible));
        passwordButton.setAttribute('aria-label', isVisible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
      });
    })();
  </script>
</body>
</html>
