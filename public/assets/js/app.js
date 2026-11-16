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
    const partyDropdown = form.querySelector('#party-dropdown');
    const partyOptions = [...form.querySelectorAll('[data-party-option]')];
    const partyEmpty = form.querySelector('[data-party-empty]');
    const topParty = document.querySelector('#top-party-pill');
    const currentBalance = form.querySelector('[data-current-balance]');
    const currentDirection = form.querySelector('[data-current-direction]');
    const nextBalance = form.querySelector('[data-next-balance]');
    const nextDirection = form.querySelector('[data-next-direction]');
    const total = form.querySelector('[data-total]');
    let balance = null;
    let balanceRequest = 0;

    const selectedType = () => radios.find((radio) => radio.checked)?.value || 'sell';
    const closePartyDropdown = () => {
      partyDropdown.classList.add('hidden');
      search.setAttribute('aria-expanded', 'false');
    };
    const setBalanceBadge = (badge, value) => {
      badge.classList.remove('hidden', 'is-owed', 'is-received', 'is-neutral');
      if (value > 0) {
        badge.textContent = 'عليه';
        badge.classList.add('is-owed');
      } else if (value < 0) {
        badge.textContent = 'له';
        badge.classList.add('is-received');
      } else {
        badge.textContent = 'متزن';
        badge.classList.add('is-neutral');
      }
    };
    const resetBalance = () => {
      balance = null;
      currentBalance.textContent = 'اختر طرفًا';
      currentBalance.className = 'text-muted';
      currentDirection.className = 'balance-badge hidden';
      updatePreview();
    };
    const filterParties = (showDropdown = false) => {
      const type = selectedType();
      const query = search.value.trim().toLocaleLowerCase();
      let selectedPartyIsInvalid = false;
      let visibleOptions = 0;
      [...partySelect.options].forEach((option, index) => {
        if (index === 0) return;
        const supportsType = (option.dataset.operations || '').split(' ').includes(type);
        const matchesSearch = query === '' || option.dataset.search.toLocaleLowerCase().includes(query);
        option.disabled = !supportsType;
        option.hidden = !supportsType || !matchesSearch;
        if (option.selected && !supportsType) selectedPartyIsInvalid = true;
      });
      partyOptions.forEach((button) => {
        const option = [...partySelect.options].find((item) => item.value === button.dataset.partyId);
        const supportsType = option && !option.disabled;
        const matchesSearch = query === '' || button.dataset.search.toLocaleLowerCase().includes(query);
        const visible = supportsType && matchesSearch;
        button.classList.toggle('hidden', !visible);
        button.setAttribute('aria-selected', option && option.selected ? 'true' : 'false');
        if (visible) visibleOptions += 1;
      });
      partyEmpty.classList.toggle('hidden', visibleOptions > 0);
      if (showDropdown) {
        partyDropdown.classList.remove('hidden');
        search.setAttribute('aria-expanded', 'true');
      } else {
        closePartyDropdown();
      }
      if (selectedPartyIsInvalid) {
        partySelect.value = '';
        search.value = '';
        search.setCustomValidity('اختر طرفًا متوافقًا مع نوع الحركة.');
        partyOptions.forEach((button) => button.setAttribute('aria-selected', 'false'));
        if (topParty) topParty.textContent = 'اختر طرفًا';
        balanceRequest += 1;
        resetBalance();
      }
    };
    const selectParty = (partyId) => {
      const option = [...partySelect.options].find((item) => item.value === partyId);
      const button = partyOptions.find((item) => item.dataset.partyId === partyId);
      if (!option || !button || option.disabled) return;
      partySelect.value = partyId;
      search.value = button.dataset.name;
      search.setCustomValidity('');
      if (topParty) topParty.textContent = button.dataset.name;
      filterParties(false);
      refreshBalance();
    };
    const updatePartyQuery = () => {
      const selectedButton = partyOptions.find((button) => button.dataset.partyId === partySelect.value);
      if (selectedButton && search.value.trim() !== selectedButton.dataset.name) {
        partySelect.value = '';
        if (topParty) topParty.textContent = 'اختر طرفًا';
        balanceRequest += 1;
        resetBalance();
      }
      search.setCustomValidity(partySelect.value ? '' : 'اختر طرفًا من نتائج البحث.');
      filterParties(true);
    };
    const refreshType = () => {
      const type = selectedType();
      const usesWeight = type === 'sell' || type === 'supply';
      filterParties(false);
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
      if (balance === null) {
        nextBalance.textContent = '—';
        nextBalance.className = 'text-muted';
        nextDirection.className = 'balance-badge hidden';
        return;
      }
      const type = selectedType();
      let delta = ['sell', 'pay_supplier'].includes(type) ? amount : -amount;
      if (type === 'sell') delta -= parsePiasters(form.querySelector('[data-pay-now]').value);
      const next = balance + delta;
      nextBalance.textContent = `${money(next)} جنيه`;
      nextBalance.className = next > 0 ? 'text-debit' : next < 0 ? 'text-credit' : 'text-muted';
      setBalanceBadge(nextDirection, next);
    };
    const refreshBalance = async () => {
      const requestId = ++balanceRequest;
      const id = partySelect.value;
      balance = null;
      currentBalance.className = 'text-muted';
      currentBalance.textContent = id ? 'جارٍ التحميل…' : 'اختر طرفًا';
      currentDirection.className = 'balance-badge hidden';
      updatePreview();
      if (!id) return;
      try {
        const response = await fetch(appUrl(`/api/parties/${encodeURIComponent(id)}/balance`), { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('تعذر جلب الرصيد');
        const data = await response.json();
        if (requestId !== balanceRequest || partySelect.value !== id) return;
        balance = Number(data.balance) || 0;
        currentBalance.textContent = `${money(balance)} جنيه`;
        currentBalance.className = balance > 0 ? 'text-debit' : balance < 0 ? 'text-credit' : 'text-muted';
        setBalanceBadge(currentDirection, balance);
        updatePreview();
      } catch {
        if (requestId !== balanceRequest || partySelect.value !== id) return;
        currentBalance.textContent = 'تعذر تحميل الرصيد';
        currentBalance.className = 'text-warning';
      }
    };
    radios.forEach((radio) => radio.addEventListener('change', refreshType));
    form.querySelectorAll('[data-weight], [data-price], [data-amount], [data-pay-now]').forEach((input) => input.addEventListener('input', updatePreview));
    partySelect.addEventListener('change', refreshBalance);
    search.addEventListener('input', updatePartyQuery);
    search.addEventListener('focus', () => filterParties(true));
    search.addEventListener('blur', (event) => {
      if (!(event.relatedTarget instanceof Element) || !event.relatedTarget.closest('.party-picker')) {
        closePartyDropdown();
      }
    });
    search.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closePartyDropdown();
      } else if (event.key === 'ArrowDown') {
        const firstOption = partyOptions.find((button) => !button.classList.contains('hidden'));
        if (firstOption) {
          event.preventDefault();
          firstOption.focus();
        }
      } else if (event.key === 'Enter' && !partySelect.value) {
        const firstOption = partyOptions.find((button) => !button.classList.contains('hidden'));
        if (firstOption && !partyDropdown.classList.contains('hidden')) {
          event.preventDefault();
          selectParty(firstOption.dataset.partyId);
        }
      }
    });
    partyOptions.forEach((button) => button.addEventListener('click', () => selectParty(button.dataset.partyId)));
    document.addEventListener('click', (event) => {
      if (!event.target.closest('.party-picker')) closePartyDropdown();
    });
    refreshType();
    if (partySelect.value) {
      const selectedButton = partyOptions.find((button) => button.dataset.partyId === partySelect.value);
      if (selectedButton) {
        search.value = selectedButton.dataset.name;
        if (topParty) topParty.textContent = selectedButton.dataset.name;
      }
      search.setCustomValidity('');
      refreshBalance();
    } else {
      search.setCustomValidity('اختر طرفًا من نتائج البحث.');
    }
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