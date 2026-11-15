window.AdminBulk = {
  toggleAll(masterCheckbox, selector) {
    document.querySelectorAll(selector).forEach((cb) => {
      cb.checked = masterCheckbox.checked;
    });
    this.updateCount(selector);
  },

  updateCount(selector) {
    const checked = document.querySelectorAll(selector + ':checked').length;
    const badge = document.getElementById('selectedCountBadge');
    if (badge) {
      badge.textContent = checked + ' selected';
    }
  }
};
