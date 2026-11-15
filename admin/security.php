<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$pdo = Database::getConnection();
Migrator::ensureSchemaReady($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    if ($action === 'unblock_ip') {
        $ip = trim((string) ($_POST['ip_address'] ?? ''));
        if ($ip !== '') {
            $pdo->prepare('DELETE FROM blocked_ips WHERE ip_address = ?')->execute([$ip]);
            log_activity('admin', (int) $admin['id'], 'ip_unblocked', 'security', null, "Unblocked IP: {$ip}");
            set_flash('success', "IP address {$ip} unblocked successfully.");
        }
    } elseif ($action === 'block_ip') {
        $ip = trim((string) ($_POST['ip_address'] ?? ''));
        $reason = sanitize_string($_POST['reason'] ?? 'Manual admin block', 200);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            $pdo->prepare(
                'INSERT INTO blocked_ips (ip_address, reason, blocked_until) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_until = DATE_ADD(NOW(), INTERVAL 24 HOUR)'
            )->execute([$ip, $reason]);
            log_activity('admin', (int) $admin['id'], 'ip_blocked', 'security', null, "Blocked IP: {$ip} ({$reason})");
            set_flash('success', "IP address {$ip} blocked for 24 hours.");
        }
    }
    redirect('security.php');
}

$blockedIps = $pdo->query('SELECT * FROM blocked_ips ORDER BY created_at DESC LIMIT 50')->fetchAll();
$securityLogs = $pdo->query('SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 100')->fetchAll();
$rateLimits = $pdo->query('SELECT * FROM rate_limits ORDER BY expires_at DESC LIMIT 50')->fetchAll();

$pageTitle = 'Security Monitoring & Abuse Protection';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Security Monitoring &amp; Abuse Protection</h1>
      <p class="text-sm text-slate-600 mt-1">Review brute-force attempts, rate-limit triggers, blocked IPs, and security audit logs.</p>
    </div>
  </div>

  <!-- Manual IP Blocker Form -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
    <h2 class="text-base font-bold text-[#151515]">Manual IP Block / Restriction</h2>
    <form method="POST" class="grid sm:grid-cols-3 gap-4 items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="block_ip">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">IP Address</label>
        <input type="text" name="ip_address" required placeholder="192.168.1.50" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono-num">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Notes</label>
        <input type="text" name="reason" required placeholder="Suspicious automated traffic" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
      </div>
      <div>
        <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 transition">
          Block IP for 24h
        </button>
      </div>
    </form>
  </div>

  <div class="grid lg:grid-cols-2 gap-6">
    <!-- Blocked IPs Table -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
      <h2 class="text-base font-bold text-[#151515]">Active Blocked IPs</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-[#F8F9FC] text-slate-500 uppercase">
            <tr>
              <th class="px-3 py-2.5">IP Address</th>
              <th class="px-3 py-2.5">Reason</th>
              <th class="px-3 py-2.5">Blocked Until</th>
              <th class="px-3 py-2.5 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($blockedIps)): ?>
              <tr><td colspan="4" class="py-4 text-center text-slate-400">No IPs currently blocked.</td></tr>
            <?php else: ?>
              <?php foreach ($blockedIps as $bip): ?>
                <tr class="hover:bg-slate-50">
                  <td class="px-3 py-2.5 font-mono-num font-bold text-rose-600"><?= e($bip['ip_address']) ?></td>
                  <td class="px-3 py-2.5 text-slate-700"><?= e($bip['reason']) ?></td>
                  <td class="px-3 py-2.5 font-mono-num text-slate-500"><?= e($bip['blocked_until'] ?: 'Permanent') ?></td>
                  <td class="px-3 py-2.5 text-right">
                    <form method="POST" class="inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="unblock_ip">
                      <input type="hidden" name="ip_address" value="<?= e($bip['ip_address']) ?>">
                      <button type="submit" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Unblock</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Active Rate Limits -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
      <h2 class="text-base font-bold text-[#151515]">Active Rate Limit Buckets</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-[#F8F9FC] text-slate-500 uppercase">
            <tr>
              <th class="px-3 py-2.5">IP Address</th>
              <th class="px-3 py-2.5">Bucket</th>
              <th class="px-3 py-2.5 text-right">Attempts</th>
              <th class="px-3 py-2.5 text-right">Expires At</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($rateLimits)): ?>
              <tr><td colspan="4" class="py-4 text-center text-slate-400">No active rate limit entries.</td></tr>
            <?php else: ?>
              <?php foreach ($rateLimits as $rl): ?>
                <tr class="hover:bg-slate-50">
                  <td class="px-3 py-2.5 font-mono-num"><?= e($rl['ip_address']) ?></td>
                  <td class="px-3 py-2.5 font-mono-num text-[#7138D8]"><?= e($rl['bucket']) ?></td>
                  <td class="px-3 py-2.5 text-right font-mono-num font-bold"><?= (int) $rl['attempts'] ?></td>
                  <td class="px-3 py-2.5 text-right font-mono-num text-slate-400"><?= e($rl['expires_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Security Audit Logs Table -->
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
      <h2 class="text-base font-bold text-[#151515]">Security &amp; Abuse Event Logs</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-xs text-left">
        <thead class="bg-[#F8F9FC] text-slate-500 uppercase">
          <tr>
            <th class="px-4 py-3">Severity</th>
            <th class="px-3 py-3">Event Type</th>
            <th class="px-3 py-3">IP Address</th>
            <th class="px-3 py-3">Details</th>
            <th class="px-4 py-3 text-right">Timestamp</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($securityLogs)): ?>
            <tr><td colspan="5" class="py-6 text-center text-slate-400">No security events logged.</td></tr>
          <?php else: ?>
            <?php foreach ($securityLogs as $sl): ?>
              <?php
              $sevColor = $sl['severity'] === 'critical' ? 'bg-rose-100 text-rose-700' : ($sl['severity'] === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700');
              ?>
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $sevColor ?>"><?= e($sl['severity']) ?></span>
                </td>
                <td class="px-3 py-3 font-mono-num font-semibold text-[#151515]"><?= e($sl['event_type']) ?></td>
                <td class="px-3 py-3 font-mono-num text-slate-600"><?= e($sl['ip_address']) ?></td>
                <td class="px-3 py-3 text-slate-700 max-w-md truncate"><?= e($sl['details']) ?></td>
                <td class="px-4 py-3 text-right font-mono-num text-slate-400"><?= e($sl['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
