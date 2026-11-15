<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'activate') {
        $slug = sanitize_string($_POST['slug'] ?? '', 60);
        $res = Theme::activate($slug, (int) $admin['id']);
        set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? $res['message'] : $res['error']);
    } elseif ($action === 'sync') {
        Theme::syncWithFilesystem();
        set_flash('success', 'Scanned /themes/ directory and synchronized available themes.');
    }
    redirect('themes.php');
}

$themes = Theme::all();
$rootPrefix = app_root_prefix();
$pageTitle = 'Theme Manager';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Theme Manager</h1>
      <p class="text-sm text-slate-600 mt-1">Manage frontend visual themes. Activating a theme updates the public site and user panel globally; the admin workspace stays clean and fixed.</p>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="sync">
      <button type="submit" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-[#151515] hover:bg-slate-50">
        Rescan /themes/ Folder
      </button>
    </form>
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($themes as $theme): ?>
      <?php
      $slug = (string) $theme['slug'];
      $isActive = (int) $theme['is_active'] === 1;
      $previewImg = APP_ROOT . '/themes/' . $slug . '/assets/images/preview.svg';
      $previewUrl = is_file($previewImg) ? ($rootPrefix . 'themes/' . $slug . '/assets/images/preview.svg') : '';
      ?>
      <div class="bg-white border rounded-2xl overflow-hidden flex flex-col justify-between <?= $isActive ? 'border-[#7138D8] ring-2 ring-[#7138D8]/20' : 'border-slate-200' ?>">
        <div>
          <div class="bg-slate-100 border-b border-slate-200 aspect-[16/10] overflow-hidden relative flex items-center justify-center">
            <?php if ($previewUrl): ?>
              <img src="<?= e($previewUrl) ?>" alt="<?= e($theme['name']) ?> preview" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-xs text-slate-500 font-medium">No preview.svg</div>
            <?php endif; ?>
            <?php if ($isActive): ?>
              <span class="absolute top-3 right-3 px-2.5 py-1 rounded-lg bg-[#16A36A] text-white text-[11px] font-bold shadow-sm">
                Active Theme
              </span>
            <?php endif; ?>
          </div>

          <div class="p-5 space-y-2">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-base font-bold text-[#151515]"><?= e($theme['name']) ?></h2>
              <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[11px] font-mono-num text-slate-600">v<?= e($theme['version']) ?></span>
            </div>
            <p class="text-xs text-slate-500">Slug: <code class="font-mono-num text-[#7138D8]"><?= e($slug) ?></code> · Author: <?= e($theme['author']) ?></p>
            <p class="text-xs text-slate-600 leading-relaxed pt-1"><?= e($theme['description']) ?></p>
          </div>
        </div>

        <div class="p-5 pt-0 flex items-center gap-2.5">
          <a
            href="<?= e($rootPrefix) ?>index.php?preview_theme=<?= e($slug) ?>"
            target="_blank"
            rel="noopener"
            class="flex-1 py-2 rounded-xl border border-slate-200 text-center text-xs font-semibold text-[#151515] hover:bg-slate-50 transition"
          >
            Preview Live
          </a>
          <?php if ($isActive): ?>
            <span class="flex-1 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-center text-xs font-bold text-[#16A36A]">
              Currently Active
            </span>
          <?php else: ?>
            <form method="POST" class="flex-1">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="activate">
              <input type="hidden" name="slug" value="<?= e($slug) ?>">
              <button type="submit" class="w-full py-2 rounded-xl bg-[#7138D8] hover:bg-[#5E28C2] text-white text-xs font-semibold transition">
                Activate Theme
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
