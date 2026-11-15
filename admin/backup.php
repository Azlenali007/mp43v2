<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('all');

$pdo = Database::getConnection();
if (!is_dir(BACKUPS_PATH)) {
    @mkdir(BACKUPS_PATH, 0755, true);
}

if (isset($_GET['download'])) {
    $file = basename((string) $_GET['download']);
    $full = BACKUPS_PATH . '/' . $file;
    if (preg_match('/^backup_[a-zA-Z0-9_-]+\.sql$/', $file) && file_exists($full)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- SMM Panel MySQL Backup\n-- Generated: " . date('c') . "\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
    foreach ($tables as $table) {
        $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $dump .= "DROP TABLE IF EXISTS `{$table}`;\n" . ($createRow['Create Table'] ?? '') . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();
        foreach ($rows as $row) {
            $cols = array_map(fn($c) => '`' . $c . '`', array_keys($row));
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
            $dump .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        $dump .= "\n";
    }
    $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    $filename = 'backup_' . date('Ymd_His') . '.sql';
    file_put_contents(BACKUPS_PATH . '/' . $filename, $dump, LOCK_EX);
    log_activity('admin', (int) $admin['id'], 'database_backup_created', 'backup', null, $filename);
    set_flash('success', "Database backup created: {$filename}");
    redirect('backup.php');
}

$files = array_reverse(glob(BACKUPS_PATH . '/backup_*.sql') ?: []);
$pageTitle = 'Database Backup';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="max-w-3xl space-y-6">
  <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold text-[#151515]">MySQL Database Backup</h2>
      <p class="text-xs text-slate-600 mt-1">Export a full SQL snapshot of all tables and data via PHP PDO.</p>
    </div>
    <form method="POST" action="backup.php">
      <?= csrf_field() ?>
      <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Generate SQL Backup</button>
    </form>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h3 class="text-base font-bold text-[#151515] mb-3">Saved Backups</h3>
    <?php if (empty($files)): ?>
      <p class="text-sm text-slate-500">No backups generated yet.</p>
    <?php else: ?>
      <div class="divide-y divide-slate-100 text-xs">
        <?php foreach ($files as $f): ?>
          <?php $bn = basename($f); ?>
          <div class="py-3 flex items-center justify-between gap-4">
            <div>
              <p class="font-mono-num font-semibold text-[#151515]"><?= e($bn) ?></p>
              <p class="text-slate-400"><?= number_format(filesize($f) / 1024, 1) ?> KB · <?= e(date('d M Y, H:i', filemtime($f))) ?></p>
            </div>
            <a href="backup.php?download=<?= urlencode($bn) ?>" class="px-3.5 py-1.5 rounded-lg border border-slate-200 font-semibold text-[#7138D8] hover:bg-purple-50">Download .sql</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
