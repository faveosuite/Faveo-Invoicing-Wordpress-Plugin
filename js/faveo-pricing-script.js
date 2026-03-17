document.addEventListener('DOMContentLoaded', function () {

  const parseMoney = (str) => {
    if (!str) return null;
    const cleaned = String(str).replace(/,/g, '').replace(/[^\d.]/g, '');
    return cleaned === '' ? null : parseFloat(cleaned);
  };

 const formatMoney = (num, currency) => {
  if (num === null || isNaN(num)) return '';

  let value = Number(num).toString();

  let [intPart, decPart = ''] = value.split('.');

  // Ensure at least 3 digits for checking
  decPart = decPart.padEnd(3, '0');

  let firstTwo = decPart.substring(0, 2);
  const thirdDigit = parseInt(decPart[2], 10);

  let finalDecimals = parseInt(firstTwo, 10);

  // ✅ Round only if 3rd digit > 4
  if (thirdDigit > 4) {
    finalDecimals += 1;
  }

  // Handle carry (e.g., 99 → 100)
  if (finalDecimals === 100) {
    intPart = (parseInt(intPart, 10) + 1).toString();
    finalDecimals = 0;
  }

  const finalValue = intPart + '.' + finalDecimals.toString().padStart(2, '0');

  const locale = currency === '₹' ? 'en-IN' : undefined;

  const formatted = Number(finalValue).toLocaleString(locale, {
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

    // 🔑 Map wrapper => visible count
    const wrapperCountMap = new Map();
    const seenKeysMap = new Map();

    productCards.forEach(card => {
      if (String(card.dataset.group) !== String(groupId)) return;

      const wrapper = card.closest('.products-wrapper');
      if (!wrapper) return;

      if (!wrapperCountMap.has(wrapper)) {
        wrapperCountMap.set(wrapper, 0);
        seenKeysMap.set(wrapper, new Set());
      }

      const seenKeys = seenKeysMap.get(wrapper);

      const productKey =
        card.dataset.productKey ||
        card.querySelector('h1')?.textContent ||
        '';

      // Hide duplicate products per-wrapper
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
        wrapperCountMap.set(wrapper, wrapperCountMap.get(wrapper) + 1);
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
        wrapperCountMap.set(wrapper, wrapperCountMap.get(wrapper) + 1);
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
  let originalPrice = null;

  if (yearlyMode && hasToggle && days >= 365) {
    // Yearly shown as per-month → divide original too
    originalPrice = yearlyAttr / 12;
  } else if (!yearlyMode && days >= 28 && days < 365) {
    // Monthly mode
    originalPrice = monthlyAttr;
  } else if (!hasToggle && days >= 365) {
    // Non-toggle yearly plans
    originalPrice = yearlyAttr;
  }

  originalStrike.textContent = formatMoney(originalPrice, currency);
  showEl(originalStrike, offerPct > 0 && originalPrice !== null);
}

        showEl(card, true);
        seenKeys.add(productKey);
        wrapperCountMap.set(wrapper, wrapperCountMap.get(wrapper) + 1);
      } else {
        showEl(card, false);
      }
    });

    // ✅ APPLY data-count PER WRAPPER
    wrapperCountMap.forEach((count, wrapper) => {
      wrapper.dataset.count = count;
    });
  }

  /**
   * TOGGLE GROUPS
   */
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

  /**
   * GROUPS WITHOUT TOGGLE
   */
  const groupsWithToggle = new Set(toggles.map(t => t.dataset.group));
  const processed = new Set();

  productCards.forEach(card => {
    const g = card.dataset.group;
    const wrapper = card.closest('.products-wrapper');
    if (!wrapper) return;

    const key = `${g}-${wrapper.dataset.instance || wrapper}`;
    if (processed.has(key)) return;
    processed.add(key);

    if (!groupsWithToggle.has(g)) {
      updateGroup({ checked: false }, g);
    }
  });

});