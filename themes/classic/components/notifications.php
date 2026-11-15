<?php
declare(strict_types=1);
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Forbidden');
}
$rp = $rootPrefix ?? app_root_prefix();
?>
<?php if (!empty($notifMode) && $notifMode === 'flash'): ?>
  <?php if (!empty($flash)): ?>
    <?php
    $flashCls = match ($flash['type'] ?? 'info') {
        'success' => 'bg-emerald-50 border-emerald-200 text-[#16A36A]',
        'error'   => 'bg-rose-50 border-rose-200 text-rose-700',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        default   => 'bg-purple-50 border-purple-200 text-[#7138D8]',
    };
    ?>
    <div class="mb-6 px-4 py-3 rounded-xl border text-sm font-medium <?= $flashCls ?>" role="alert">
      <?= e($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open" class="relative p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition" aria-label="Notifications">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <?php if (($unreadCount ?? 0) > 0): ?>
        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"><?= (int) $unreadCount ?></span>
      <?php endif; ?>
    </button>
    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden z-40">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <span class="text-sm font-semibold text-[#151515]">Notifications</span>
        <?php if (($unreadCount ?? 0) > 0): ?>
          <form method="POST" action="<?= e($rp) ?>api/wallet.php" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_notifications_read">
            <button type="submit" class="text-xs font-medium text-[#7138D8] hover:underline">Mark all read</button>
          </form>
        <?php endif; ?>
      </div>
      <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
        <?php if (empty($notifications)): ?>
          <div class="p-4 text-xs text-slate-500 text-center">No notifications yet.</div>
        <?php else: ?>
          <?php foreach ($notifications as $notif): ?>
            <a href="<?= e($notif['link'] ?: ($rp . 'dashboard.php')) ?>" class="block px-4 py-3 hover:bg-slate-50 transition <?= (int) ($notif['is_read'] ?? 1) === 0 ? 'bg-purple-50/40' : '' ?>">
              <p class="text-xs font-semibold text-[#151515]"><?= e($notif['title']) ?></p>
              <p class="text-xs text-slate-600 mt-0.5"><?= e($notif['message']) ?></p>
              <p class="text-[11px] text-slate-400 mt-1"><?= e(time_ago($notif['created_at'])) ?></p>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
