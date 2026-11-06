(() => {
  const basePath = document.querySelector('meta[name="app-base-path"]')?.content || '';
  const appUrl = (path) => `${basePath}/${path.replace(/^\/+/, '')}`;
  const form = document.querySelector('[data-operation-form]');
  const money = (piasters) => {
    const value = Math.abs(piasters) / 100;
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: piasters % 100 ? 2 : 0, maximumFractionDigits: 2 }).format(value);
  };
  const parsePiasters = (value) => {
    const number = Number(value);
    return Number.isFinite(number) ? Math.round(number * 100) : 0;
  };

  if (form) {
    const radios = [...form.querySelectorAll('[data-operation-type]')];
    const weightFields = form.querySelector('[data-weight-fields]');
    const amountFields = form.querySelector('[data-amount-fields]');
    const payNowWrap = form.querySelector('[data-pay-now-wrap]');
    const partySelect = form.querySelector('#party-id');
    const search = form.querySelector('#party-search');
    const currentBalance = form.querySelector('[data-current-balance]');
    const nextBalance = form.querySelector('[data-next-balance]');
    const total = form.querySelector('[data-total]');
    const totalDirection = form.querySelector('[data-total-direction]');
    let balance = null;

    const selectedType = () => radios.find((radio) => radio.checked)?.value || 'sell';
    const refreshType = () => {
      const type = selectedType();
      const usesWeight = type === 'sell' || type === 'supply';
      radios.forEach((radio) => radio.closest('.operation-type')?.classList.toggle('is-selected', radio.checked));
      weightFields.classList.toggle('hidden', !usesWeight);
      amountFields.classList.toggle('hidden', usesWeight);
      payNowWrap.classList.toggle('hidden', type !== 'sell');
      updatePreview();
    };
    const computedAmount = () => {
      if (selectedType() === 'sell' || selectedType() === 'supply') {
        return Math.round(Number(form.querySelector('[data-weight]').value || 0) * parsePiasters(form.querySelector('[data-price]').value));
      }
      return parsePiasters(form.querySelector('[data-amount]').value);
    };
    const updatePreview = () => {
      const amount = computedAmount();
      const isDebit = ['sell', 'pay_supplier'].includes(selectedType());
      total.textContent = money(amount);
      total.className = isDebit ? 'text-debit' : 'text-credit';
      totalDirection.textContent = isDebit ? 'عليه' : 'له';
      if (balance === null) {
        nextBalance.textContent = '—';
        nextBalance.className = 'text-muted';
        return;
      }
      const type = selectedType();
      let delta = ['sell', 'pay_supplier'].includes(type) ? amount : -amount;
      if (type === 'sell') delta -= parsePiasters(form.querySelector('[data-pay-now]').value);
      const next = balance + delta;
      const label = next > 0 ? `عليه ${money(next)}` : next < 0 ? `له ${money(next)}` : 'متزن';
      nextBalance.textContent = `${label} جنيه`;
      nextBalance.className = next > 0 ? 'text-debit' : next < 0 ? 'text-credit' : 'text-muted';
    };
    const refreshBalance = async () => {
      const id = partySelect.value;
      balance = null;
      currentBalance.className = 'text-muted';
      currentBalance.textContent = id ? 'جارٍ التحميل…' : 'اختر طرفًا';
      updatePreview();
      if (!id) return;
      try {
        const response = await fetch(appUrl(`/api/parties/${encodeURIComponent(id)}/balance`), { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('تعذر جلب الرصيد');
        const data = await response.json();
        balance = Number(data.balance) || 0;
        const label = balance > 0 ? `عليه ${money(balance)}` : balance < 0 ? `له ${money(balance)}` : 'متزن';
        currentBalance.textContent = `${label} جنيه`;
        currentBalance.className = balance > 0 ? 'text-debit' : balance < 0 ? 'text-credit' : 'text-muted';
        updatePreview();
      } catch {
        currentBalance.textContent = 'تعذر تحميل الرصيد';
        currentBalance.className = 'text-warning';
      }
    };
    radios.forEach((radio) => radio.addEventListener('change', refreshType));
    form.querySelectorAll('[data-weight], [data-price], [data-amount], [data-pay-now]').forEach((input) => input.addEventListener('input', updatePreview));
    partySelect.addEventListener('change', refreshBalance);
    search.addEventListener('input', () => {
      const query = search.value.trim().toLocaleLowerCase();
      [...partySelect.options].forEach((option, index) => {
        if (index > 0) option.hidden = query !== '' && !option.dataset.search.toLocaleLowerCase().includes(query);
      });
    });
    refreshType();
    if (partySelect.value) refreshBalance();
  }

  document.querySelectorAll('form[data-confirm]').forEach((confirmForm) => {
    confirmForm.addEventListener('submit', (event) => {
      if (!window.confirm(confirmForm.dataset.confirm)) event.preventDefault();
    });
  });

  document.querySelectorAll('.party-more').forEach((menu) => {
    menu.addEventListener('toggle', () => {
      if (!menu.open) {
        menu.classList.remove('opens-up');
        menu.style.removeProperty('--party-menu-max-height');
        return;
      }

      const summary = menu.querySelector(':scope > summary');
      const content = menu.querySelector(':scope > .party-menu');
      if (!summary || !content) return;

      const trigger = summary.getBoundingClientRect();
      const spaceAbove = Math.max(0, trigger.top - 12);
      const spaceBelow = Math.max(0, window.innerHeight - trigger.bottom - 12);
      const openUp = content.scrollHeight > spaceBelow && spaceAbove > spaceBelow;
      const availableSpace = openUp ? spaceAbove : spaceBelow;

      menu.classList.toggle('opens-up', openUp);
      menu.style.setProperty('--party-menu-max-height', `${Math.max(96, availableSpace)}px`);
    });
  });

  const banner = document.querySelector('#offline-banner');
  if (banner) {
    const updateOnline = () => banner.classList.toggle('hidden', navigator.onLine);
    window.addEventListener('online', updateOnline);
    window.addEventListener('offline', updateOnline);
    updateOnline();
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      document.documentElement.classList.toggle('dark');
      localStorage.setItem('ledger-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    });
  });
  if (localStorage.getItem('ledger-theme') === 'dark') document.documentElement.classList.add('dark');
})();