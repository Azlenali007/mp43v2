window.ToolsApp = {
  async run(slug, payload) {
    return window.SMMApp.postJson('api.php', { slug, ...payload });
  },
  copy(text, btn) {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(() => {
      if (btn) {
        const orig = btn.innerText;
        btn.innerText = 'Copied!';
        setTimeout(() => { btn.innerText = orig; }, 1400);
      }
    });
  }
};
