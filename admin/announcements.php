<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = sanitize_string($_POST['title'] ?? '', 180);
        $content = sanitize_string($_POST['content'] ?? '', 2000);
        $type = in_array($_POST['type'] ?? '', ['info', 'update', 'offer', 'maintenance'], true) ? $_POST['type'] : 'info';
        if ($title !== '' && $content !== '') {
            $pdo->prepare('INSERT INTO announcements (title, content, type, is_active, created_by) VALUES (?, ?, ?, 1, ?)')
                ->execute([$title, $content, $type, (int) $admin['id']]);
            set_flash('success', 'Announcement published.');
        }
    } elseif ($action === 'toggle') {
        $id = validate_positive_int($_POST['id'] ?? null);
        if ($id) {
            $pdo->prepare('UPDATE announcements SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
            set_flash('success', 'Announcement visibility toggled.');
        }
    } elseif ($action === 'delete') {
        $id = validate_positive_int($_POST['id'] ?? null);
        if ($id) {
            $pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
            set_flash('success', 'Announcement removed.');
        }
    }
    redirect('announcements.php');
}

$items = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Announcements';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid lg:grid-cols-12 gap-6 items-start">
  <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Publish Announcement</h2>
    <form method="POST" action="announcements.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Title</label>
        <input type="text" name="title" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Type</label>
        <select name="type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <option value="info">Information</option>
          <option value="update">Service Update</option>
          <option value="offer">Bonus / Offer</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Message</label>
        <textarea name="content" rows="4" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
      </div>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Publish</button>
    </form>
  </div>

  <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Published Announcements</h2>
    <?php if (empty($items)): ?>
      <p class="text-sm text-slate-500">No announcements published yet.</p>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($items as $a): ?>
          <div class="py-4 flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-bold text-[#151515]"><?= e($a['title']) ?></p>
              <p class="text-xs text-slate-600 mt-1"><?= e($a['content']) ?></p>
              <p class="text-[11px] text-slate-400 mt-1"><?= e(date('d M Y, H:i', strtotime($a['created_at']))) ?> · <?= $a['is_active'] ? 'Visible' : 'Hidden' ?></p>
            </div>
            <div class="flex items-center gap-2 shrink-0 text-xs">
              <form method="POST" action="announcements.php" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button type="submit" name="action" value="toggle" class="font-semibold text-[#7138D8] hover:underline"><?= $a['is_active'] ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="POST" action="announcements.php" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button type="submit" name="action" value="delete" class="font-semibold text-rose-600 hover:underline">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
