<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_categories');
require_once __DIR__ . '/../classes/Category.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = sanitize_string($_POST['name'] ?? '', 120);
        if ($name === '') {
            set_flash('error', 'Category name is required.');
        } else {
            $id = Category::create([
                'name'        => $name,
                'icon_key'    => sanitize_string($_POST['icon_key'] ?? 'globe', 60),
                'platform'    => sanitize_string($_POST['icon_key'] ?? 'general', 60),
                'description' => sanitize_string($_POST['description'] ?? '', 255),
                'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                'status'      => $_POST['status'] ?? 'active',
            ]);
            log_activity('admin', (int) $admin['id'], 'category_created', 'category', $id, $name);
            set_flash('success', 'Category created.');
        }
    } elseif ($action === 'update') {
        $id = validate_positive_int($_POST['id'] ?? null);
        $name = sanitize_string($_POST['name'] ?? '', 120);
        if ($id && $name !== '') {
            Category::update($id, [
                'name'        => $name,
                'icon_key'    => sanitize_string($_POST['icon_key'] ?? 'globe', 60),
                'platform'    => sanitize_string($_POST['icon_key'] ?? 'general', 60),
                'description' => sanitize_string($_POST['description'] ?? '', 255),
                'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                'status'      => $_POST['status'] ?? 'active',
            ]);
            log_activity('admin', (int) $admin['id'], 'category_updated', 'category', $id, $name);
            set_flash('success', 'Category updated.');
        }
    } elseif ($action === 'delete') {
        $id = validate_positive_int($_POST['id'] ?? null);
        if ($id) {
            $res = Category::deleteProtected($id);
            set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Category deleted.' : $res['error']);
        }
    }
    redirect('categories.php');
}

$categories = Category::all(false);
$icons = icon_registry();
$editCat = null;
if (!empty($_GET['edit'])) {
    $editCat = Category::find((int) $_GET['edit']);
}

$pageTitle = 'Categories Management';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid lg:grid-cols-12 gap-6 items-start">
  <!-- Add / Edit Category Form -->
  <div class="lg:col-span-4 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]"><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
    <form method="POST" action="categories.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editCat ? 'update' : 'create' ?>">
      <?php if ($editCat): ?><input type="hidden" name="id" value="<?= (int) $editCat['id'] ?>"><?php endif; ?>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name</label>
        <input type="text" name="name" value="<?= e($editCat['name'] ?? '') ?>" required placeholder="e.g. Instagram Followers" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Platform / Category Icon</label>
        <select name="icon_key" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
          <?php foreach ($icons as $key => $info): ?>
            <option value="<?= e($key) ?>" <?= ($editCat['icon_key'] ?? '') === $key ? 'selected' : '' ?>><?= e($info['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Order</label>
          <input type="number" name="sort_order" value="<?= (int) ($editCat['sort_order'] ?? 0) ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
          <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="active" <?= ($editCat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="disabled" <?= ($editCat['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Disabled</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
        <input type="text" name="description" value="<?= e($editCat['description'] ?? '') ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm">
      </div>

      <div class="flex items-center gap-2">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">
          <?= $editCat ? 'Update Category' : 'Create Category' ?>
        </button>
        <?php if ($editCat): ?>
          <a href="categories.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Categories List + Bulk Operations -->
  <div class="lg:col-span-8 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-bold text-[#151515]">Categories (<?= count($categories) ?>)</h2>
      <span id="selectedCountBadge" class="text-xs font-semibold text-slate-500">0 selected</span>
    </div>

    <?php if (empty($categories)): ?>
      <p class="text-sm text-slate-500 py-8 text-center">No categories created yet.</p>
    <?php else: ?>
      <form method="POST" action="../api/bulk-operations.php" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="categories">

        <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 flex flex-wrap items-center gap-2">
          <select name="bulk_action" required class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
            <option value="">Bulk Action...</option>
            <option value="enable">Enable Selected</option>
            <option value="disable">Disable Selected</option>
            <option value="move_services">Move Associated Services To Target Category</option>
            <option value="delete">Delete Selected (Protected if services exist)</option>
          </select>
          <select name="target_category_id" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
            <option value="">Target Category (for Move)...</option>
            <?php foreach ($categories as $tc): ?>
              <option value="<?= (int) $tc['id'] ?>"><?= e($tc['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#151515] text-white text-xs font-semibold">Apply</button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
                <th class="py-2.5 pr-3"><input type="checkbox" onclick="window.AdminBulk.toggleAll(this, '.cat-cb')"></th>
                <th class="py-2.5 px-3">Category</th>
                <th class="py-2.5 px-3 text-right">Services</th>
                <th class="py-2.5 px-3 text-right">Sort</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 pl-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($categories as $c): ?>
                <tr class="hover:bg-slate-50/60">
                  <td class="py-3 pr-3"><input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" class="cat-cb" onchange="window.AdminBulk.updateCount('.cat-cb')"></td>
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-3">
                      <?= render_icon_badge($c['icon_key'] ?: 'globe', 'w-8 h-8', 'p-1.5') ?>
                      <span class="font-semibold text-[#151515]"><?= e($c['name']) ?></span>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-right font-mono-num text-xs"><?= (int) $c['services_count'] ?></td>
                  <td class="py-3 px-3 text-right font-mono-num text-xs"><?= (int) $c['sort_order'] ?></td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md text-xs font-semibold <?= $c['status'] === 'active' ? 'bg-emerald-50 text-[#16A36A]' : 'bg-slate-100 text-slate-600' ?>"><?= e(ucfirst($c['status'])) ?></span>
                  </td>
                  <td class="py-3 pl-3 text-right text-xs whitespace-nowrap space-x-2">
                    <a href="categories.php?edit=<?= (int) $c['id'] ?>" class="font-semibold text-[#7138D8] hover:underline">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
