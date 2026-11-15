document.addEventListener('DOMContentLoaded', () => {
  const testBtn = document.getElementById('testDbBtn');
  if (!testBtn) return;

  testBtn.addEventListener('click', async () => {
    const statusBox = document.getElementById('dbTestResult');
    const form = document.getElementById('dbForm');
    if (!form || !statusBox) return;

    testBtn.disabled = true;
    const origText = testBtn.textContent;
    testBtn.textContent = 'Testing Connection...';
    statusBox.className = 'mt-4 p-3 rounded-xl text-sm border bg-slate-50 border-slate-200 text-slate-700';
    statusBox.textContent = 'Connecting to MySQL server via PDO...';
    statusBox.classList.remove('hidden');

    try {
      const fd = new FormData(form);
      fd.set('action', 'test_connection');
      const res = await fetch('database.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.ok) {
        statusBox.className = 'mt-4 p-3 rounded-xl text-sm border bg-emerald-50 border-emerald-200 text-[#16A36A] font-medium';
        statusBox.textContent = data.message;
      } else {
        statusBox.className = 'mt-4 p-3 rounded-xl text-sm border bg-rose-50 border-rose-200 text-rose-700 font-medium';
        statusBox.textContent = data.message || 'Database connection failed.';
      }
    } catch (err) {
      statusBox.className = 'mt-4 p-3 rounded-xl text-sm border bg-rose-50 border-rose-200 text-rose-700 font-medium';
      statusBox.textContent = 'Request error while testing database connection.';
    } finally {
      testBtn.disabled = false;
      testBtn.textContent = origText;
    }
  });
});
