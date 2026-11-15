<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin('manage_payments');
require_once __DIR__ . '/../includes/payment-functions.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    $code = $_POST['method_code'] ?? '';

    if ($action === 'toggle' && in_array($code, ['razorpay', 'manual'], true)) {
        $newEnabled = (int) ($_POST['is_enabled'] ?? 0) === 1 ? 1 : 0;
        $pdo->prepare('UPDATE payment_methods SET is_enabled = ?, updated_by = ? WHERE code = ?')->execute([$newEnabled, (int) $admin['id'], $code]);
        log_activity('admin', (int) $admin['id'], 'payment_method_toggled_' . $code, 'payment_method', null, 'Enabled=' . $newEnabled);
        set_flash('success', ucfirst($code) . ' payment method ' . ($newEnabled ? 'enabled' : 'disabled') . '.');
        redirect('payment-methods.php');
    }

    if ($action === 'save_razorpay') {
        $existing = Payment::getMethod('razorpay');
        $oldCfg = $existing['config'] ?? [];
        $keyId = trim((string) ($_POST['key_id'] ?? ''));
        $newSecret = trim((string) ($_POST['key_secret'] ?? ''));
        $newWebhook = trim((string) ($_POST['webhook_secret'] ?? ''));
        $mode = ($_POST['mode'] ?? 'test') === 'live' ? 'live' : 'test';
        $isEnabled = !empty($_POST['is_enabled']) ? 1 : 0;
        $minAmt = max(1.0, (float) ($_POST['min_amount'] ?? 10));
        $maxAmt = max($minAmt, (float) ($_POST['max_amount'] ?? 100000));

        $cfg = [
            'key_id'         => $keyId,
            'key_secret'     => $newSecret !== '' ? encrypt_secret($newSecret) : ($oldCfg['key_secret'] ?? ''),
            'webhook_secret' => $newWebhook !== '' ? encrypt_secret($newWebhook) : ($oldCfg['webhook_secret'] ?? ''),
        ];

        $pdo->prepare('UPDATE payment_methods SET is_enabled = ?, mode = ?, config_json = ?, min_amount = ?, max_amount = ?, updated_by = ? WHERE code = "razorpay"')
            ->execute([$isEnabled, $mode, json_encode($cfg), $minAmt, $maxAmt, (int) $admin['id']]);
        log_activity('admin', (int) $admin['id'], 'razorpay_settings_updated');
        set_flash('success', 'Razorpay settings saved securely.');
        redirect('payment-methods.php');
    }

    if ($action === 'test_razorpay') {
        $rzp = Razorpay::fromDatabase();
        if (!$rzp || !$rzp['client']) {
            set_flash('error', 'Save valid Razorpay Key ID and Key Secret before testing connection.');
        } else {
            $test = $rzp['client']->testConnection();
            set_flash($test['ok'] ? 'success' : 'error', $test['ok'] ? 'Razorpay API connection verified successfully (' . strtoupper($rzp['mode']) . ' mode).' : 'Razorpay connection failed: ' . $test['error']);
        }
        redirect('payment-methods.php');
    }

    if ($action === 'save_manual') {
        $existing = Payment::getMethod('manual');
        $oldCfg = $existing['config'] ?? [];
        $qrPath = (string) ($oldCfg['qr_code_path'] ?? '');
        if (!empty($_FILES['qr_code']['name'])) {
            $up = validate_and_store_upload($_FILES['qr_code'], 'payments');
            if (!$up['ok']) {
                set_flash('error', 'QR Upload Error: ' . $up['error']);
                redirect('payment-methods.php');
            }
            $qrPath = $up['path'];
        }

        $isEnabled = !empty($_POST['is_enabled']) ? 1 : 0;
        $minAmt = max(1.0, (float) ($_POST['min_amount'] ?? 10));
        $maxAmt = max($minAmt, (float) ($_POST['max_amount'] ?? 100000));
        $cfg = [
            'method_name'    => sanitize_string($_POST['method_name'] ?? 'Manual UPI / Bank Transfer', 100),
            'upi_id'         => sanitize_string($_POST['upi_id'] ?? '', 120),
            'qr_code_path'   => $qrPath,
            'bank_name'      => sanitize_string($_POST['bank_name'] ?? '', 120),
            'account_holder' => sanitize_string($_POST['account_holder'] ?? '', 120),
            'account_number' => sanitize_string($_POST['account_number'] ?? '', 60),
            'ifsc_code'      => strtoupper(sanitize_string($_POST['ifsc_code'] ?? '', 30)),
            'instructions'   => sanitize_string($_POST['instructions'] ?? '', 1000),
        ];

        $pdo->prepare('UPDATE payment_methods SET name = ?, is_enabled = ?, config_json = ?, min_amount = ?, max_amount = ?, updated_by = ? WHERE code = "manual"')
            ->execute([$cfg['method_name'], $isEnabled, json_encode($cfg), $minAmt, $maxAmt, (int) $admin['id']]);
        log_activity('admin', (int) $admin['id'], 'manual_payment_settings_updated');
        set_flash('success', 'Manual payment settings saved.');
        redirect('payment-methods.php');
    }
}

$razorpay = Payment::getMethod('razorpay');
$manual = Payment::getMethod('manual');
$rzpCfg = $razorpay['config'] ?? [];
$manCfg = $manual['config'] ?? [];
$webhookUrl = app_base_url() . '/api/razorpay-webhook.php';

$pageTitle = 'Payment Methods';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="space-y-6" x-data="{ openConfig: '' }">
  <div>
    <h2 class="text-xl font-bold text-[#151515]">Payment Methods Management</h2>
    <p class="text-sm text-slate-600 mt-1">Independently enable, disable, and configure Razorpay and Manual Payment options. Disabled methods never appear on the User Add Funds page.</p>
  </div>

  <!-- Two Separate Management Cards -->
  <div class="grid md:grid-cols-2 gap-6">
    <!-- Card 1: Razorpay -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between space-y-5">
      <div class="space-y-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-[#151515]"><?= e($razorpay['name'] ?? 'Razorpay') ?></h3>
            <p class="text-xs text-slate-500 mt-0.5">Automatic Online Payment (INR) · <?= strtoupper(e($razorpay['mode'] ?? 'test')) ?> Mode</p>
          </div>
          <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border <?= !empty($razorpay['is_enabled']) ? 'bg-emerald-50 text-[#16A36A] border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>">
            <?= !empty($razorpay['is_enabled']) ? 'Enabled' : 'Disabled' ?>
          </span>
        </div>

        <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 text-xs space-y-1">
          <p><span class="text-slate-500">Key ID:</span> <span class="font-mono-num font-semibold"><?= !empty($rzpCfg['key_id']) ? e($rzpCfg['key_id']) : 'Not configured' ?></span></p>
          <p><span class="text-slate-500">Key Secret:</span> <span class="font-semibold"><?= !empty($rzpCfg['key_secret']) ? 'Configured (Encrypted)' : 'Not configured' ?></span></p>
          <p><span class="text-slate-500">Last Updated:</span> <?= e(date('d M Y, H:i', strtotime($razorpay['updated_at'] ?? 'now'))) ?></p>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
        <form method="POST" action="payment-methods.php" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="method_code" value="razorpay">
          <input type="hidden" name="is_enabled" value="<?= !empty($razorpay['is_enabled']) ? '0' : '1' ?>">
          <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border <?= !empty($razorpay['is_enabled']) ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-[#16A36A] hover:bg-emerald-50' ?>">
            <?= !empty($razorpay['is_enabled']) ? 'Disable' : 'Enable' ?>
          </button>
        </form>

        <div class="flex items-center gap-2">
          <form method="POST" action="payment-methods.php" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_razorpay">
            <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">Test Connection</button>
          </form>
          <button type="button" @click="openConfig = openConfig === 'razorpay' ? '' : 'razorpay'" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Configure</button>
        </div>
      </div>
    </div>

    <!-- Card 2: Manual Payment -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between space-y-5">
      <div class="space-y-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-[#151515]"><?= e($manual['name'] ?? 'Manual Payment') ?></h3>
            <p class="text-xs text-slate-500 mt-0.5">UPI / QR Code / Bank Transfer (Admin Approval)</p>
          </div>
          <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border <?= !empty($manual['is_enabled']) ? 'bg-emerald-50 text-[#16A36A] border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>">
            <?= !empty($manual['is_enabled']) ? 'Enabled' : 'Disabled' ?>
          </span>
        </div>

        <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 text-xs space-y-1">
          <p><span class="text-slate-500">UPI ID:</span> <span class="font-mono-num font-semibold"><?= !empty($manCfg['upi_id']) ? e($manCfg['upi_id']) : 'Not set' ?></span></p>
          <p><span class="text-slate-500">Bank Account:</span> <span class="font-semibold"><?= !empty($manCfg['account_number']) ? e($manCfg['bank_name'] . ' · ' . $manCfg['account_number']) : 'Not set' ?></span></p>
          <p><span class="text-slate-500">Last Updated:</span> <?= e(date('d M Y, H:i', strtotime($manual['updated_at'] ?? 'now'))) ?></p>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
        <form method="POST" action="payment-methods.php" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="method_code" value="manual">
          <input type="hidden" name="is_enabled" value="<?= !empty($manual['is_enabled']) ? '0' : '1' ?>">
          <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold border <?= !empty($manual['is_enabled']) ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-[#16A36A] hover:bg-emerald-50' ?>">
            <?= !empty($manual['is_enabled']) ? 'Disable' : 'Enable' ?>
          </button>
        </form>

        <button type="button" @click="openConfig = openConfig === 'manual' ? '' : 'manual'" class="px-4 py-2 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5B24C0]">Configure</button>
      </div>
    </div>
  </div>

  <!-- Razorpay Configuration Panel -->
  <div x-show="openConfig === 'razorpay'" x-cloak class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 max-w-3xl">
    <h3 class="text-base font-bold text-[#151515]">Configure Razorpay Integration</h3>
    <div class="p-3.5 rounded-xl bg-[#F8F9FC] border border-slate-200 text-xs">
      <p class="font-semibold text-slate-700">Webhook Endpoint URL (configure inside Razorpay Dashboard &rarr; Webhooks):</p>
      <p class="font-mono-num text-[#7138D8] mt-1 break-all"><?= e($webhookUrl) ?></p>
    </div>

    <form method="POST" action="payment-methods.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_razorpay">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Mode</label>
          <select name="mode" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="test" <?= ($razorpay['mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test Mode</option>
            <option value="live" <?= ($razorpay['mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live Mode</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Razorpay Key ID</label>
          <input type="text" name="key_id" value="<?= e($rzpCfg['key_id'] ?? '') ?>" placeholder="rzp_test_..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Razorpay Key Secret (leave blank to retain saved)</label>
          <input type="password" name="key_secret" placeholder="<?= !empty($rzpCfg['key_secret']) ? '••••••••••••' : 'Enter Key Secret' ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Webhook Secret (leave blank to retain saved)</label>
          <input type="password" name="webhook_secret" placeholder="<?= !empty($rzpCfg['webhook_secret']) ? '••••••••••••' : 'Enter Webhook Secret' ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Deposit (INR)</label>
          <input type="number" step="0.01" min="1" name="min_amount" value="<?= e((string) ($razorpay['min_amount'] ?? 10)) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Maximum Deposit (INR)</label>
          <input type="number" step="0.01" min="1" name="max_amount" value="<?= e((string) ($razorpay['max_amount'] ?? 100000)) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
      </div>

      <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
        <input type="checkbox" name="is_enabled" value="1" <?= !empty($razorpay['is_enabled']) ? 'checked' : '' ?>> Enable Razorpay on Add Funds page
      </label>

      <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Razorpay Settings</button>
    </form>
  </div>

  <!-- Manual Payment Configuration Panel -->
  <div x-show="openConfig === 'manual'" x-cloak class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 max-w-3xl">
    <h3 class="text-base font-bold text-[#151515]">Configure Manual Payment Details</h3>
    <form method="POST" action="payment-methods.php" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_manual">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method Display Name</label>
          <input type="text" name="method_name" value="<?= e($manCfg['method_name'] ?? $manual['name'] ?? 'Manual UPI / Bank Transfer') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">UPI ID</label>
          <input type="text" name="upi_id" value="<?= e($manCfg['upi_id'] ?? '') ?>" placeholder="merchant@upi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
          <input type="text" name="bank_name" value="<?= e($manCfg['bank_name'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Account Holder Name</label>
          <input type="text" name="account_holder" value="<?= e($manCfg['account_holder'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Account Number</label>
          <input type="text" name="account_number" value="<?= e($manCfg['account_number'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">IFSC Code</label>
          <input type="text" name="ifsc_code" value="<?= e($manCfg['ifsc_code'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Deposit (INR)</label>
          <input type="number" step="0.01" min="1" name="min_amount" value="<?= e((string) ($manual['min_amount'] ?? 10)) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Maximum Deposit (INR)</label>
          <input type="number" step="0.01" min="1" name="max_amount" value="<?= e((string) ($manual['max_amount'] ?? 100000)) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">QR Code Image (Optional)</label>
        <input type="file" name="qr_code" accept="image/jpeg,image/png,image/webp" class="w-full text-xs">
        <?php if (!empty($manCfg['qr_code_path'])): ?>
          <img src="../<?= e($manCfg['qr_code_path']) ?>" alt="Current QR" class="mt-2 w-28 h-28 object-contain border border-slate-200 rounded-xl p-1">
        <?php endif; ?>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Instructions for Users</label>
        <textarea name="instructions" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"><?= e($manCfg['instructions'] ?? '') ?></textarea>
      </div>

      <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
        <input type="checkbox" name="is_enabled" value="1" <?= !empty($manual['is_enabled']) ? 'checked' : '' ?>> Enable Manual Payment on Add Funds page
      </label>

      <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0]">Save Manual Payment Settings</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
