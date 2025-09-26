document.addEventListener('DOMContentLoaded', function () {

  const parseMoney = (str) => {
    if (str === null || str === undefined) return null;
    const s = String(str).trim().toLowerCase();
    if (!s || s === 'custom') return null;
    const clean = s.replace(/,/g, ''); 
    if (!/^\d*(\.\d+)?$/.test(clean)) return null;
    return parseFloat(clean);
  };

  const formatMoney = (num, currency) => {
    if (num === null || isNaN(num)) return currency + '0';
    const isINR = String(currency).trim() === '₹';
    const formatted = isINR
      ? Number(num).toLocaleString('en-IN', { maximumFractionDigits: 0 })
      : Number(num).toLocaleString(undefined, { maximumFractionDigits: 0 });
    return currency + formatted;
  };

  const showEl = (el, show) => {
    if (!el) return;
    el.style.display = show ? '' : 'none';
  };

  const toggles = document.querySelectorAll('.pricing-toggle');
  const productCards = document.querySelectorAll('.product-container');

  function updateGroup(toggle, groupId) {
    const yearlyMode = toggle.checked;

    productCards.forEach((card) => {
      if (card.dataset.group !== String(groupId)) return;

      const days = parseInt(card.dataset.days || '0', 10) || 0;
      const priceEl = card.querySelector('.product-pricing h2');
      if (!priceEl) return;

      const monthlyAttr = priceEl.getAttribute('data-monthly-price') || '';
      const yearlyAttr = priceEl.getAttribute('data-yearly-price') || '';
      const currency = card.dataset.currency || '$';
      const offerPct = parseFloat(card.dataset.offer || '0') || 0;
      const originalStrike = card.querySelector('.original-price');

      const monthlyOrig = parseMoney(originalStrike?.dataset.monthlyOrig || '');
      const yearlyOrig = parseMoney(originalStrike?.dataset.yearlyOrig || '');

      // Hide 14-day plans
      if (days === 14) {
        showEl(card, false);
        return;
      }

      // Custom Pricing
      const isCustomPricing =
        String(monthlyAttr).toLowerCase().includes('custom') ||
        String(yearlyAttr).toLowerCase().includes('custom') ||
        String(priceEl.textContent).toLowerCase().includes('custom');

      if (isCustomPricing) {
        priceEl.textContent = 'Custom Pricing';
        if (yearlyMode) showEl(card, days >= 365);
        else showEl(card, days >= 28 && days < 365);
        if (originalStrike) showEl(originalStrike, false);
        return;
      }

      if (yearlyMode) {
        // Yearly with toggle
        if (days >= 365) {
          let yearlyTotal = parseMoney(yearlyAttr);
          if (yearlyTotal === null) { showEl(card, false); return; }

          if (originalStrike && yearlyOrig) {
            const origPerMonth = yearlyOrig / 12;
            originalStrike.textContent = formatMoney(origPerMonth, currency);
            showEl(originalStrike, true);
          }

          if (offerPct > 0) yearlyTotal = yearlyTotal - (yearlyTotal * (offerPct / 100));
          const perMonth = yearlyTotal / 12;

          priceEl.textContent = formatMoney(perMonth, currency);
          showEl(card, true);
        } else {
          showEl(card, false);
        }
      } else {
        // Monthly with toggle
        if (days >= 28 && days < 365) {
          let monthlyPrice = parseMoney(monthlyAttr);
          if (monthlyPrice === null) { showEl(card, false); return; }

          if (originalStrike && monthlyOrig) {
            originalStrike.textContent = formatMoney(monthlyOrig, currency);
            showEl(originalStrike, true);
          }

          if (offerPct > 0) monthlyPrice = monthlyPrice - (monthlyPrice * (offerPct / 100));
          priceEl.textContent = formatMoney(monthlyPrice, currency);
          showEl(card, true);
        } else {
          showEl(card, false);
        }
      }
    });
  }

  // Initialize toggles
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

  // ✅ Handle groups with no toggle: show yearly price directly
  const allGroups = new Set([...productCards].map(card => card.dataset.group));
  allGroups.forEach(groupId => {
    const groupToggles = document.querySelectorAll(`.pricing-toggle[data-group="${groupId}"]`);
    if (groupToggles.length > 0) return; // Skip groups with toggle

    // No toggle → show yearly price
    productCards.forEach(card => {
      if (card.dataset.group !== groupId) return;

      const days = parseInt(card.dataset.days || '0', 10) || 0;
      const priceEl = card.querySelector('.product-pricing h2');
      if (!priceEl) return;
      const yearlyAttr = priceEl.getAttribute('data-yearly-price') || '';
      const offerPct = parseFloat(card.dataset.offer || '0') || 0;
      const currency = card.dataset.currency || '$';
      const originalStrike = card.querySelector('.original-price');
      const yearlyOrig = parseMoney(originalStrike?.dataset.yearlyOrig || '');
      const yearlyTotal = parseMoney(yearlyAttr);

      if (days >= 365 && yearlyTotal !== null) {
        let finalPrice = yearlyTotal;
        if (offerPct > 0) finalPrice = yearlyTotal - (yearlyTotal * (offerPct / 100));
        priceEl.textContent = formatMoney(finalPrice, currency);

        if (originalStrike && yearlyOrig) {
          originalStrike.textContent = formatMoney(yearlyOrig, currency);
          showEl(originalStrike, true);
        }

        showEl(card, true);
      } else {
        showEl(card, false);
      }
    });
  });

});
