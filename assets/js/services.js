document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('serviceSearchInput');
  const categoryFilter = document.getElementById('serviceCategoryFilter');
  const rows = document.querySelectorAll('[data-service-row]');

  function filterServices() {
    const q = (searchInput ? searchInput.value : '').trim().toLowerCase();
    const cat = categoryFilter ? categoryFilter.value : '';

    rows.forEach((row) => {
      const text = (row.getAttribute('data-search') || '').toLowerCase();
      const rowCat = row.getAttribute('data-category') || '';
      const matchesQ = !q || text.includes(q);
      const matchesCat = !cat || rowCat === cat;
      row.style.display = (matchesQ && matchesCat) ? '' : 'none';
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterServices);
  if (categoryFilter) categoryFilter.addEventListener('change', filterServices);
});
