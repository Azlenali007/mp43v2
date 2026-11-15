<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$tool = Tools::requireEnabled('instagram-bio');

$pageTitle = $tool['name'];
require __DIR__ . '/../includes/user-header.php';
?>
<link rel="stylesheet" href="assets/css/tools.css">
<div class="space-y-6" x-data="{
  brand: '',
  niche: '',
  audience: 'Creators & Founders',
  cta: 'DM or click link below',
  tone: 'Clean & Authority',
  loading: false,
  error: '',
  result: null,
  async submit() {
    if (this.loading) return;
    this.error = '';
    this.loading = true;
    try {
      const res = await window.ToolsApp.run('instagram-bio', {
        brand: this.brand,
        niche: this.niche,
        audience: this.audience,
        cta: this.cta,
        tone: this.tone
      });
      if (res && res.ok) {
        this.result = res.result;
      } else {
        this.error = res?.message || 'Unable to generate bios.';
      }
    } catch (e) {
      this.error = 'Network error while generating bios.';
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
        <p class="text-sm text-slate-600"><?= e($tool['description']) ?> (Strictly enforced &le; 150 characters)</p>
      </div>
    </div>
    <a href="index.php" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50 self-start">All Tools</a>
  </div>

  <div class="grid lg:grid-cols-12 gap-6 items-start">
    <form @submit.prevent="submit" class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Name / Brand Handle</label>
        <input type="text" x-model="brand" required placeholder="e.g. Alex Rivera | Growth Studio" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Niche &amp; Value Proposition</label>
        <input type="text" x-model="niche" required placeholder="e.g. Helping coaches scale short-form Reels" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Audience</label>
        <input type="text" x-model="audience" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Call To Action (CTA)</label>
        <input type="text" x-model="cta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Style / Tone</label>
        <select x-model="tone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
          <option>Clean & Authority</option>
          <option>Minimalist Aesthetic</option>
          <option>Bold & Direct</option>
          <option>Friendly & Emoji-Styled</option>
        </select>
      </div>

      <template x-if="error">
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-700" x-text="error"></div>
      </template>

      <button type="submit" :disabled="loading" class="w-full py-2.5 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-sm font-semibold disabled:opacity-50 transition">
        <span x-text="loading ? 'Generating Bios...' : (result ? 'Regenerate Bios' : 'Generate Instagram Bios')"></span>
      </button>
    </form>

    <div class="lg:col-span-7 space-y-4">
      <template x-if="!result">
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-sm text-slate-500">
          Every generated bio strictly respects Instagram's 150-character profile limit.
        </div>
      </template>

      <template x-if="result">
        <div class="grid sm:grid-cols-2 gap-4">
          <template x-for="(item, idx) in result.bios" :key="idx">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 tool-output-card flex flex-col justify-between gap-4">
              <div class="space-y-2.5">
                <div class="flex items-center justify-between gap-2">
                  <span class="px-2 py-0.5 rounded-md bg-purple-50 text-[#7138D8] text-[11px] font-bold" x-text="item.label"></span>
                  <span class="text-[11px] font-mono-num font-semibold" :class="item.char_count <= 150 ? 'text-[#16A36A]' : 'text-rose-600'" x-text="item.char_count + ' / 150 chars'"></span>
                </div>
                <p class="text-sm text-[#151515] whitespace-pre-line leading-relaxed" x-text="item.bio"></p>
              </div>
              <button type="button" @click="window.ToolsApp.copy(item.bio, $el)" class="w-full py-2 rounded-xl border border-slate-200 text-xs font-semibold hover:bg-slate-50 transition">
                Copy Bio
              </button>
            </div>
          </template>
        </div>
      </template>
    </div>
  </div>
</div>
<script src="assets/js/tools.js"></script>
<?php require __DIR__ . '/../includes/user-footer.php'; ?>
