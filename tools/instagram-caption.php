<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$tool = Tools::requireEnabled('instagram-caption');

$pageTitle = $tool['name'];
require __DIR__ . '/../includes/user-header.php';
?>
<link rel="stylesheet" href="assets/css/tools.css">
<div class="space-y-6" x-data="{
  topic: '',
  niche: 'Lifestyle & Creator',
  tone: 'Engaging & Conversational',
  language: 'English',
  loading: false,
  error: '',
  result: null,
  async submit() {
    if (this.loading) return;
    this.error = '';
    this.loading = true;
    try {
      const res = await window.ToolsApp.run('instagram-caption', {
        topic: this.topic,
        niche: this.niche,
        tone: this.tone,
        language: this.language
      });
      if (res && res.ok) {
        this.result = res.result;
      } else {
        this.error = res?.message || 'Unable to generate captions.';
      }
    } catch (e) {
      this.error = 'Network error while generating captions.';
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
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Post / Reel Topic</label>
        <textarea x-model="topic" rows="3" required placeholder="e.g. Behind-the-scenes morning coffee routine and launching my new preset pack..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#7138D8]"></textarea>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Niche / Industry</label>
        <input type="text" x-model="niche" placeholder="Fitness, Fashion, SaaS, Food..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tone</label>
          <select x-model="tone" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            <option>Engaging & Conversational</option>
            <option>Short & Punchy</option>
            <option>Bold & Inspirational</option>
            <option>Witty & Playful</option>
            <option>Educational / Value</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Language</label>
          <input type="text" x-model="language" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <template x-if="error">
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700" x-text="error"></div>
      </template>

      <div class="flex items-center gap-2.5">
        <button type="submit" :disabled="loading" class="flex-1 py-2.5 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-sm font-semibold disabled:opacity-50 transition">
          <span x-text="loading ? 'Generating...' : (result ? 'Regenerate Captions' : 'Generate Captions')"></span>
        </button>
      </div>
    </form>

    <div class="lg:col-span-7 space-y-4">
      <template x-if="!result && !loading">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
          Enter your post topic on the left to generate structured Instagram captions and niche hashtags.
        </div>
      </template>

      <template x-if="result">
        <div class="space-y-4">
          <template x-for="(cap, idx) in result.captions" :key="idx">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 tool-output-card space-y-2.5">
              <div class="flex items-center justify-between gap-2">
                <span class="px-2.5 py-0.5 rounded-md bg-purple-50 text-[#7138D8] text-xs font-bold" x-text="cap.style"></span>
                <button type="button" @click="window.ToolsApp.copy(cap.text + (cap.cta ? '\n\n' + cap.cta : ''), $el)" class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">
                  Copy Caption
                </button>
              </div>
              <p class="text-sm text-[#151515] whitespace-pre-line leading-relaxed" x-text="cap.text"></p>
              <template x-if="cap.cta">
                <p class="text-xs font-semibold text-[#16A36A]" x-text="'CTA: ' + cap.cta"></p>
              </template>
            </div>
          </template>

          <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3" x-show="result.hashtags && result.hashtags.length">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-[#151515]">Recommended Hashtags</h3>
              <button type="button" @click="window.ToolsApp.copy((result.hashtags || []).join(' '), $el)" class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">
                Copy All Hashtags
              </button>
            </div>
            <p class="text-xs text-[#7138D8] font-mono-num leading-relaxed" x-text="(result.hashtags || []).join(' ')"></p>
            <template x-if="result.posting_tip">
              <p class="text-xs text-slate-600 pt-2 border-t border-slate-100" x-text="'Tip: ' + result.posting_tip"></p>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
<script src="assets/js/tools.js"></script>
<?php require __DIR__ . '/../includes/user-footer.php'; ?>
