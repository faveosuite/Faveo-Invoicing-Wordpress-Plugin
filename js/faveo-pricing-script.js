document.addEventListener('DOMContentLoaded', function () {

  const parseMoney = (str) => {
    if (!str) return null;
    const cleaned = String(str).replace(/,/g, '').replace(/[^\d.]/g, '');
    return cleaned === '' ? null : parseFloat(cleaned);
  };

  const formatMoney = (num, currency) => {
  if (num === null || isNaN(num)) return '';

  const value = Number(num);

  const locale = currency === '₹' ? 'en-IN' : undefined;

  const formatted = value.toLocaleString(locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  return (currency || '') + formatted;
};


  const showEl = (el, show) => {
    if (el) el.style.display = show ? '' : 'none';
  };

  const toggles = Array.from(document.querySelectorAll('.pricing-toggle'));
  const productCards = Array.from(document.querySelectorAll('.product-container'));

  function updateGroup(toggle, groupId) {
    const yearlyMode = !!(toggle && toggle.checked);
    const seenKeys = new Set();
    let visibleCount = 0; // ✅ COUNT VISIBLE PRODUCTS

    productCards.forEach(card => {
      if (!card.dataset.group || String(card.dataset.group) !== String(groupId)) return;

      const productKey =
        card.dataset.productKey ||
        card.querySelector('h1')?.textContent ||
        '';

      // Hide duplicate products
      if (productKey && seenKeys.has(productKey)) {
        showEl(card, false);
        return;
      }

      const hasToggle = card.dataset.hasToggle === '1';
      const days = parseInt(card.dataset.days || '0', 10);
      const priceEl = card.querySelector('.product-pricing h2');
      if (!priceEl) return;

      // Free product
      if (card.dataset.isFree === "1") {
        priceEl.textContent = 'Free';
        showEl(card.querySelector('.original-price'), false);
        showEl(card, true);
        seenKeys.add(productKey);
        visibleCount++;
        return;
      }

      const monthlyAttr = parseMoney(card.dataset.monthly);
      const yearlyAttr  = parseMoney(card.dataset.yearly);
      const currency    = card.dataset.currencySymbol || '';
      const offerPct    = parseFloat(card.dataset.offer || '0');

      // Custom pricing
      const isCustom = ['1', 'true'].includes(card.dataset.addToContact || '0');
      if (isCustom) {
        priceEl.textContent = 'Custom Pricing';
        showEl(card.querySelector('.original-price'), false);
        showEl(card, true);
        seenKeys.add(productKey);
        visibleCount++;
        return;
      }

      let finalPrice = null;

      if (yearlyMode && hasToggle && days >= 365) {
        finalPrice = yearlyAttr - (yearlyAttr * (offerPct / 100));
        finalPrice /= 12;
      } else if (!yearlyMode && days >= 28 && days < 365) {
        finalPrice = monthlyAttr - (monthlyAttr * (offerPct / 100));
      } else if (!hasToggle && days >= 365) {
        finalPrice = yearlyAttr - (yearlyAttr * (offerPct / 100));
      }

      if (finalPrice !== null) {
        priceEl.textContent = formatMoney(finalPrice, currency);

        const originalStrike = card.querySelector('.original-price');
        if (originalStrike) {
          originalStrike.textContent = formatMoney(
            yearlyMode ? yearlyAttr : monthlyAttr,
            currency
          );
          showEl(originalStrike, offerPct > 0);
        }

        showEl(card, true);
        seenKeys.add(productKey);
        visibleCount++;
      } else {
        showEl(card, false);
      }
    });

    // ✅ UPDATE data-count (THIS FIXES YOUR UI)
    const wrapper =
      document.querySelector(`.products-wrapper[data-group="${groupId}"]`) ||
      document.querySelector('.products-wrapper');

    if (wrapper) {
      wrapper.dataset.count = visibleCount;
    }
  }

  // Toggle-enabled groups
  toggles.forEach(toggle => {
    const groupId = toggle.dataset.group;
    const paramValue = new URLSearchParams(window.location.search)
      .get(`pricing_group_${groupId}`);

    toggle.checked = paramValue === null ? true : paramValue === 'yearly';
    updateGroup(toggle, groupId);

    toggle.addEventListener('change', () => {
      updateGroup(toggle, groupId);
      const params = new URLSearchParams(window.location.search);
      params.set(
        `pricing_group_${groupId}`,
        toggle.checked ? 'yearly' : 'monthly'
      );
      window.history.replaceState({}, '', `${location.pathname}?${params}`);
    });
  });

  // Groups WITHOUT toggle
  const groupsWithToggle = new Set(toggles.map(t => t.dataset.group));
  const seenGroups = new Set();

  productCards.forEach(card => {
    const g = card.dataset.group;
    if (seenGroups.has(g)) return;
    seenGroups.add(g);
    if (!groupsWithToggle.has(g)) {
      updateGroup({ checked: false }, g);
    }
  });

});
