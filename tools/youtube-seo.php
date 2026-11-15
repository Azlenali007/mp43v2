<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$tool = Tools::requireEnabled('youtube-seo');

$pageTitle = $tool['name'];
require __DIR__ . '/../includes/user-header.php';
?>
<link rel="stylesheet" href="assets/css/tools.css">
<div class="space-y-6" x-data="{
  topic: '',
  keywords: '',
  audience: 'Creators & Tech Enthusiasts',
  video_type: 'Long-form YouTube Video',
  loading: false,
  error: '',
  result: null,
  async submit() {
    if (this.loading) return;
    this.error = '';
    this.loading = true;
    try {
      const res = await window.ToolsApp.run('youtube-seo', {
        topic: this.topic,
        keywords: this.keywords,
        audience: this.audience,
        video_type: this.video_type
      });
      if (res && res.ok) {
        this.result = res.result;
      } else {
        this.error = res?.message || 'Unable to generate YouTube SEO.';
      }
    } catch (e) {
      this.error = 'Network error while generating YouTube SEO.';
    } finally {
      this.loading = false;
    }
  }
}">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <?= render_icon_badge((string) $tool['icon_key'], 'w-11 h-11', 'p-2.5') ?>
      <div>
        <h1 class="text-2xl font-bold text-[#151515]"><?= e($tool['name']) ?></h1>
        <p class="text-sm text-slate-600"><?= e($tool['description']) ?></p>
      </div>
    </div>
    <a href="index.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">All Tools</a>
  </div>

  <div class="grid lg:grid-cols-12 gap-6 items-start">
    <form @submit.prevent="submit" class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Video Topic / Concept</label>
        <textarea x-model="topic" rows="3" required placeholder="e.g. How to edit viral Shorts in 10 minutes with free tools" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Keywords (comma separated)</label>
        <input type="text" x-model="keywords" placeholder="youtube shorts editing, capcut tutorial, viral video tips" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Audience</label>
        <input type="text" x-model="audience" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Video Format</label>
        <select x-model="video_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
          <option>Long-form YouTube Video</option>
          <option>YouTube Shorts</option>
          <option>Tutorial / Walkthrough</option>
          <option>Product Review</option>
        </select>
      </div>

      <template x-if="error">
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700" x-text="error"></div>
      </template>

      <button type="submit" :disabled="loading" class="w-full py-2.5 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-sm font-semibold disabled:opacity-50 transition">
        <span x-text="loading ? 'Optimizing SEO...' : (result ? 'Regenerate SEO Pack' : 'Generate YouTube SEO')"></span>
      </button>
    </form>

    <div class="lg:col-span-7 space-y-4">
      <template x-if="!result">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
          Generate high-CTR video titles, keyword-rich descriptions, and searchable tags in seconds.
        </div>
      </template>

      <template x-if="result">
        <div class="space-y-4">
          <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
            <h3 class="text-sm font-bold text-[#151515]">High-CTR Title Ideas</h3>
            <div class="space-y-2">
              <template x-for="(title, idx) in result.titles" :key="idx">
                <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 flex items-center justify-between gap-3">
                  <span class="text-sm font-semibold text-[#151515]" x-text="title"></span>
                  <button type="button" @click="window.ToolsApp.copy(title, $el)" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-xs font-semibold shrink-0 hover:bg-slate-50">Copy</button>
                </div>
              </template>
            </div>
          </div>

          <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-[#151515]">Optimized Video Description</h3>
              <button type="button" @click="window.ToolsApp.copy(result.description, $el)" class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">Copy Description</button>
            </div>
            <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed" x-text="result.description"></p>
          </div>

          <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-[#151515]">Video Tags</h3>
              <button type="button" @click="window.ToolsApp.copy((result.tags || []).join(', '), $el)" class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">Copy CSV Tags</button>
            </div>
            <p class="text-xs font-mono-num text-[#7138D8] leading-relaxed" x-text="(result.tags || []).join(', ')"></p>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
<script src="assets/js/tools.js"></script>
<?php require __DIR__ . '/../includes/user-footer.php'; ?>
