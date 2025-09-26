document.addEventListener('DOMContentLoaded', function () {

  const parseMoney = (str) => {
    if (!str || str.toLowerCase() === 'custom') return null;
    const clean = str.replace(/,/g, '');
    return /^\d*(\.\d+)?$/.test(clean) ? parseFloat(clean) : null;
  };

  const formatMoney = (num, currency) => {
    if (num === null || isNaN(num)) return currency + '0.00';
    const isINR = String(currency).trim() === '₹';
    const formatted = isINR
      ? Number(num).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : Number(num).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return currency + formatted;
  };

  const showEl = (el, show) => { if (el) el.style.display = show ? '' : 'none'; };

  const toggles = document.querySelectorAll('.pricing-toggle');
  const productCards = document.querySelectorAll('.product-container');

  function updateGroup(toggle, groupId) {
    const yearlyMode = toggle.checked;

    productCards.forEach((card) => {
      if (card.dataset.group !== String(groupId)) return;

      const days = parseInt(card.dataset.days || '0', 10);
      const priceEl = card.querySelector('.product-pricing h2');
      if (!priceEl) return;

      const monthlyAttr = priceEl.getAttribute('data-monthly-price') || '';
      const yearlyAttr = priceEl.getAttribute('data-yearly-price') || '';
      const currency = card.dataset.currency || '$';
      const offerPct = parseFloat(card.dataset.offer || '0') || 0;
      const originalStrike = card.querySelector('.original-price');

      const monthlyOrig = parseMoney(originalStrike?.dataset.monthlyOrig || '');
      const yearlyOrig = parseMoney(originalStrike?.dataset.yearlyOrig || '');

      if (days === 14) { showEl(card, false); return; }

      const isCustom = [monthlyAttr, yearlyAttr, priceEl.textContent].some(t => String(t).toLowerCase().includes('custom'));
      if (isCustom) {
        priceEl.textContent = 'Custom Pricing';
        showEl(card, yearlyMode ? days >= 365 : (days >= 28 && days < 365));
        if (originalStrike) showEl(originalStrike, false);
        return;
      }

      if (yearlyMode) {
        if (days >= 365) {
          let yearlyTotal = parseMoney(yearlyAttr);
          if (yearlyTotal === null) { showEl(card, false); return; }
          if (originalStrike && yearlyOrig) { originalStrike.textContent = formatMoney(yearlyOrig / 12, currency); showEl(originalStrike, true); }
          if (offerPct > 0) yearlyTotal -= yearlyTotal * (offerPct / 100);
          priceEl.textContent = formatMoney(yearlyTotal / 12, currency);
          showEl(card, true);
        } else showEl(card, false);
      } else {
        if (days >= 28 && days < 365) {
          let monthlyPrice = parseMoney(monthlyAttr);
          if (monthlyPrice === null) { showEl(card, false); return; }
          if (originalStrike && monthlyOrig) { originalStrike.textContent = formatMoney(monthlyOrig, currency); showEl(originalStrike, true); }
          if (offerPct > 0) monthlyPrice -= monthlyPrice * (offerPct / 100);
          priceEl.textContent = formatMoney(monthlyPrice, currency);
          showEl(card, true);
        } else showEl(card, false);
      }
    });
  }

  toggles.forEach((toggle) => {
    const groupId = toggle.dataset.group;
    const params = new URLSearchParams(window.location.search);
    toggle.checked = params.get(`pricing_group_${groupId}`) === 'yearly';
    updateGroup(toggle, groupId);
    toggle.addEventListener('change', () => {
      updateGroup(toggle, groupId);
      const p = new URLSearchParams(window.location.search);
      p.set(`pricing_group_${groupId}`, toggle.checked ? 'yearly' : 'monthly');
      window.history.replaceState({}, '', `${location.pathname}?${p.toString()}`);
    });
  });
});
