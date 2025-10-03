document.addEventListener('DOMContentLoaded', function () {

  const parseMoney = (str) => {
    if (str === null || str === undefined) return null;
    const s = String(str).trim();
    if (!s) return null;
    const cleaned = s.replace(/,/g, '').replace(/[^\d.]/g, '');
    return cleaned === '' ? null : parseFloat(cleaned);
  };

const formatMoney = (num, currency) => {
  if (num === null || isNaN(num)) return (currency || '$') + '0';
  
  const isINR = String(currency || '').trim() === '₹';
  const floored = Math.floor(Number(num)); // always round down
  
  const formatted = isINR
    ? floored.toLocaleString('en-IN')
    : floored.toLocaleString();
    
  return (currency || '$') + formatted;
};



  const showEl = (el, show) => { if (el) el.style.display = show ? '' : 'none'; };

  const toggles = Array.from(document.querySelectorAll('.pricing-toggle'));
  const productCards = Array.from(document.querySelectorAll('.product-container'));

  function updateGroup(toggle, groupId) {
    const yearlyMode = !!(toggle && toggle.checked);

    // For deduplication in this group: keep track of product keys already shown
    const seenKeys = new Set();

    // iterate cards and apply logic
    productCards.forEach(card => {
      if (String(card.dataset.group) !== String(groupId)) return;

      const productKey = String(card.dataset.productKey || card.dataset.product_key || card.dataset.id || (card.querySelector('h1')?.textContent || '')).trim();
      // if we've already decided to display a card with same key, hide duplicates
      if (productKey && seenKeys.has(productKey)) {
        showEl(card, false);
        return;
      }

      const hasToggle = String(card.dataset.hasToggle) === '1';
      const days = parseInt(card.dataset.days || '0', 10) || 0;
      const priceEl = card.querySelector('.product-pricing h2');
      if (!priceEl) return;

      // prefer container data attributes (more reliable), fallback to H2 attributes
      const monthlyAttr = (card.dataset.monthly ?? priceEl.getAttribute('data-monthly-price') ?? '');
      const yearlyAttr = (card.dataset.yearly ?? priceEl.getAttribute('data-yearly-price') ?? '');
      const currency = card.dataset.currency ?? '$';
      const offerPct = parseFloat(card.dataset.offer || '0') || 0;
      const originalStrike = card.querySelector('.original-price');

      const monthlyOrig = parseMoney(originalStrike?.dataset.monthlyOrig || '');
      const yearlyOrig = parseMoney(originalStrike?.dataset.yearlyOrig || '');

      // Hide 14-day products always
      if (days === 14) { showEl(card, false); return; }

      // detect custom pricing (robust: check data-add-to-contact and common dataset variants)
      const rawCustom = (card.dataset.addToContact ?? card.dataset.add_to_contact ?? card.dataset['add-to-contact'] ?? '');
      const isCustom = String(rawCustom || '').toLowerCase() === '1' || String(rawCustom || '').toLowerCase() === 'true';

      if (isCustom) {
        // Custom Pricing is a single plan — always show (first occurrence), hide duplicates
        priceEl.textContent = 'Custom Pricing';
        if (originalStrike) showEl(originalStrike, false);
        showEl(card, true);
        if (productKey) seenKeys.add(productKey);
        return; // skip normal price calc
      }

      // Normal price calculation
      let finalPrice = null;
      let finalOrig = null;

      if (yearlyMode && hasToggle) {
        if (days >= 365) {
          const yr = parseMoney(yearlyAttr);
          if (yr === null) { showEl(card, false); return; }
          let val = yr;
          if (offerPct > 0) val = val - (val * (offerPct / 100));
          finalPrice = val / 12;
          finalOrig = (yearlyOrig !== null) ? (yearlyOrig / 12) : null;
        } else { showEl(card, false); return; }
      } else if (!hasToggle && days >= 365) {
        const yr = parseMoney(yearlyAttr);
        if (yr === null) { showEl(card, false); return; }
        let val = yr;
        if (offerPct > 0) val = val - (val * (offerPct / 100));
        finalPrice = val;
        finalOrig = (yearlyOrig !== null) ? yearlyOrig : null;
      } else if (!yearlyMode) {
        if (days >= 28 && days < 365) {
          const mo = parseMoney(monthlyAttr);
          if (mo === null) { showEl(card, false); return; }
          let val = mo;
          if (offerPct > 0) val = val - (val * (offerPct / 100));
          finalPrice = val;
          finalOrig = (monthlyOrig !== null) ? monthlyOrig : null;
        } else { showEl(card, false); return; }
      }

      // Render normal product price + original strike (if available)
      if (finalPrice !== null) {
        priceEl.textContent = formatMoney(finalPrice, currency);
        if (originalStrike && finalOrig !== null) {
          originalStrike.textContent = formatMoney(finalOrig, currency);
          showEl(originalStrike, offerPct > 0);
        } else if (originalStrike) {
          showEl(originalStrike, false);
        }
        showEl(card, true);
        if (productKey) seenKeys.add(productKey);
      } else {
        showEl(card, false);
      }
    });
  }

 // Initialize toggles (each toggle manages its group)
toggles.forEach(toggle => {
  const groupId = toggle.dataset.group;
  const params = new URLSearchParams(window.location.search);

  // DEFAULT: yearly plan shown
  const paramValue = params.get(`pricing_group_${groupId}`);
  toggle.checked = paramValue === null ? true : paramValue === 'yearly';

  updateGroup(toggle, groupId);

  toggle.addEventListener('change', () => {
    updateGroup(toggle, groupId);
    const p = new URLSearchParams(window.location.search);
    p.set(`pricing_group_${groupId}`, toggle.checked ? 'yearly' : 'monthly');
    window.history.replaceState({}, '', `${location.pathname}?${p.toString()}`);
  });
});


  // Initialize groups that don't have a toggle element
  const groupsWithToggle = new Set(toggles.map(t => String(t.dataset.group)));
  const seenGroups = new Set();
  productCards.forEach(card => {
    const g = String(card.dataset.group);
    if (seenGroups.has(g)) return;
    seenGroups.add(g);
    if (!groupsWithToggle.has(g)) {
      updateGroup({ checked: false }, g);
    }
  });

});
