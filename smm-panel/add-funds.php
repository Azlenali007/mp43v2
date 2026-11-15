<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/includes/payment-functions.php';

$enabledMethods = get_enabled_payment_methods_for_user();
$pdo = Database::getConnection();

$payStmt = $pdo->prepare('SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
$payStmt->execute([(int) $user['id']]);
$paymentHistory = $payStmt->fetchAll();

$siteName = get_setting('site_name', 'CrownSMM');
$defaultTab = isset($enabledMethods['razorpay']) ? 'razorpay' : (isset($enabledMethods['manual']) ? 'manual' : '');

$pageTitle = 'Add Funds';
require __DIR__ . '/includes/user-header.php';
?>
<div class="space-y-6" x-data='{
  activeMethod: <?= json_encode($defaultTab) ?>,
  rzpAmount: "",
  rzpLoading: false,
  rzpFeedback: null,
  manualFeedback: null,
  manualLoading: false,
  async startRazorpay() {
    this.rzpFeedback = null;
    const amt = Number(this.rzpAmount);
    if (!amt || amt <= 0) {
      this.rzpFeedback = { ok: false, message: "Enter a valid INR deposit amount." };
      return;
    }
    this.rzpLoading = true;
    try {
      const res = await window.SMMApp.postJson("api/razorpay-create-order.php", { amount: amt });
      if (!res.ok) {
        this.rzpFeedback = { ok: false, message: res.message || "Unable to create Razorpay order." };
        this.rzpLoading = false;
        return;
      }
      const options = {
        key: res.key_id,
        amount: res.amount_paise,
        currency: res.currency,
        name: <?= json_encode($siteName) ?>,
        description: "Wallet Top-up (" + res.payment_code + ")",
        order_id: res.razorpay_order_id,
        prefill: {
          name: <?= json_encode($user["name"]) ?>,
          email: <?= json_encode($user["email"]) ?>,
          contact: <?= json_encode($user["phone"] ?? "") ?>
        },
        theme: { color: "#7138D8" },
        handler: async (response) => {
          this.rzpFeedback = { ok: true, message: "Verifying payment signature with server..." };
          const verifyRes = await window.SMMApp.postJson("api/razorpay-verify.php", {
            payment_id: res.payment_db_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature
          });
          this.rzpFeedback = verifyRes;
          if (verifyRes.ok) {
            setTimeout(() => window.location.reload(), 1200);
          }
        },
        modal: {
          ondismiss: async () => {
            await window.SMMApp.postJson("api/payment-request.php", { action: "cancel_rzp", payment_id: res.payment_db_id });
            this.rzpFeedback = { ok: false, message: "Payment checkout was cancelled." };
            this.rzpLoading = false;
          }
        }
      };
      const rzp = new Razorpay(options);
      rzp.on("payment.failed", async (resp) => {
        await window.SMMApp.postJson("api/payment-request.php", {
          action: "fail_rzp",
          payment_id: res.payment_db_id,
          reason: resp.error?.description || "Payment failed"
        });
        this.rzpFeedback = { ok: false, message: resp.error?.description || "Payment failed." };
        this.rzpLoading = false;
      });
      rzp.open();
      this.rzpLoading = false;
    } catch (e) {
      this.rzpFeedback = { ok: false, message: "Unable to initialize Razorpay checkout." };
      this.rzpLoading = false;
    }
  },
  async submitManualPayment(ev) {
    this.manualFeedback = null;
    this.manualLoading = true;
    try {
      const fd = new FormData(ev.target);
      const res = await window.SMMApp.postForm("api/manual-payment.php", fd);
      this.manualFeedback = res;
      if (res.ok) {
        ev.target.reset();
        setTimeout(() => window.location.reload(), 1400);
      }
    } catch (e) {
      this.manualFeedback = { ok: false, message: "Request error while submitting payment details." };
    } finally {
      this.manualLoading = false;
    }
  }
}'>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-[#151515]">Add Funds</h1>
      <p class="text-sm text-slate-600 mt-1">Deposit INR into your wallet using active payment methods.</p>
    </div>
    <div class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 flex items-center gap-3">
      <span class="text-xs text-slate-500">Current Balance</span>
      <span class="text-base font-bold text-[#16A36A] font-mono-num"><?= e(format_currency($user['wallet_balance'])) ?></span>
    </div>
  </div>

  <?php if (empty($enabledMethods)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
      <h2 class="text-lg font-bold text-[#151515]">No Payment Methods Available</h2>
      <p class="text-sm text-slate-600 mt-1 max-w-md mx-auto">All deposit methods are currently disabled or awaiting configuration by the administrator. Please check back later or open a support ticket.</p>
      <a href="tickets.php" class="mt-5 inline-flex px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold">Contact Support</a>
    </div>
  <?php else: ?>
    <!-- Method Selector Cards -->
    <div class="grid sm:grid-cols-2 gap-4">
      <?php if (isset($enabledMethods['razorpay'])): ?>
        <button type="button" @click="activeMethod = 'razorpay'" :class="activeMethod === 'razorpay' ? 'border-[#7138D8] bg-purple-50/50 ring-2 ring-[#7138D8]/20' : 'border-slate-200 bg-white'" class="p-5 rounded-2xl border text-left transition">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-bold text-[#151515]"><?= e($enabledMethods['razorpay']['name']) ?></h2>
            <span class="text-xs font-semibold text-[#16A36A]">Instant</span>
          </div>
          <p class="text-xs text-slate-600 mt-1"><?= e($enabledMethods['razorpay']['description']) ?></p>
          <p class="text-[11px] text-slate-500 font-mono-num mt-2">
            Min: <?= e(format_currency($enabledMethods['razorpay']['min_amount'])) ?> · Max: <?= e(format_currency($enabledMethods['razorpay']['max_amount'])) ?>
          </p>
        </button>
      <?php endif; ?>

      <?php if (isset($enabledMethods['manual'])): ?>
        <button type="button" @click="activeMethod = 'manual'" :class="activeMethod === 'manual' ? 'border-[#7138D8] bg-purple-50/50 ring-2 ring-[#7138D8]/20' : 'border-slate-200 bg-white'" class="p-5 rounded-2xl border text-left transition">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-bold text-[#151515]"><?= e($enabledMethods['manual']['name']) ?></h2>
            <span class="text-xs font-semibold text-amber-600">Manual Review</span>
          </div>
          <p class="text-xs text-slate-600 mt-1"><?= e($enabledMethods['manual']['description']) ?></p>
          <p class="text-[11px] text-slate-500 font-mono-num mt-2">
            Min: <?= e(format_currency($enabledMethods['manual']['min_amount'])) ?> · Max: <?= e(format_currency($enabledMethods['manual']['max_amount'])) ?>
          </p>
        </button>
      <?php endif; ?>
    </div>

    <!-- Razorpay Form -->
    <?php if (isset($enabledMethods['razorpay'])): ?>
      <div x-show="activeMethod === 'razorpay'" x-cloak class="bg-white border border-slate-200 rounded-2xl p-6 max-w-xl space-y-4">
        <h3 class="text-base font-bold text-[#151515]">Pay Online via Razorpay (INR)</h3>
        <p class="text-xs text-slate-600">Wallet funds are credited automatically once Razorpay verifies and confirms your transaction.</p>

        <template x-if="rzpFeedback">
          <div :class="rzpFeedback.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-3.5 rounded-xl border text-sm font-medium" x-text="rzpFeedback.message"></div>
        </template>

        <form @submit.prevent="startRazorpay" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (INR)</label>
            <input type="number" step="0.01" min="<?= (float) $enabledMethods['razorpay']['min_amount'] ?>" max="<?= (float) $enabledMethods['razorpay']['max_amount'] ?>" x-model="rzpAmount" required placeholder="e.g. 500" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num focus:outline-none focus:border-[#7138D8]">
          </div>
          <button type="submit" :disabled="rzpLoading" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] disabled:opacity-50 transition">
            <span x-text="rzpLoading ? 'Initializing Checkout...' : 'Proceed to Razorpay Checkout'"></span>
          </button>
        </form>
      </div>
      <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>

    <!-- Manual Payment Form -->
    <?php if (isset($enabledMethods['manual'])): ?>
      <?php $man = $enabledMethods['manual']; ?>
      <div x-show="activeMethod === 'manual'" x-cloak class="grid lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
          <h3 class="text-base font-bold text-[#151515]">Payment Details</h3>
          <?php if ($man['qr_code_path'] !== ''): ?>
            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 inline-block">
              <img src="<?= e($man['qr_code_path']) ?>" alt="Payment QR Code" class="w-44 h-44 object-contain rounded-lg">
            </div>
          <?php endif; ?>

          <div class="space-y-2.5 text-sm">
            <?php if ($man['upi_id'] !== ''): ?>
              <div class="p-3 rounded-xl bg-[#F8F9FC] border border-slate-200 flex items-center justify-between gap-2">
                <div>
                  <p class="text-[11px] text-slate-500">UPI ID</p>
                  <p class="font-mono-num font-semibold text-[#151515]"><?= e($man['upi_id']) ?></p>
                </div>
                <button type="button" onclick="window.SMMApp.copyText(<?= json_encode($man['upi_id']) ?>, this)" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Copy</button>
              </div>
            <?php endif; ?>

            <?php if ($man['bank_name'] !== '' || $man['account_number'] !== ''): ?>
              <div class="p-3.5 rounded-xl bg-[#F8F9FC] border border-slate-200 space-y-1.5 text-xs">
                <p class="font-semibold text-[#151515]">Bank Transfer Details</p>
                <?php if ($man['bank_name']): ?><p><span class="text-slate-500">Bank:</span> <?= e($man['bank_name']) ?></p><?php endif; ?>
                <?php if ($man['account_holder']): ?><p><span class="text-slate-500">Account Name:</span> <?= e($man['account_holder']) ?></p><?php endif; ?>
                <?php if ($man['account_number']): ?><p><span class="text-slate-500">Account No:</span> <span class="font-mono-num font-semibold"><?= e($man['account_number']) ?></span></p><?php endif; ?>
                <?php if ($man['ifsc_code']): ?><p><span class="text-slate-500">IFSC:</span> <span class="font-mono-num font-semibold"><?= e($man['ifsc_code']) ?></span></p><?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($man['instructions'] !== ''): ?>
              <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line"><?= e($man['instructions']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
          <h3 class="text-base font-bold text-[#151515]">Submit Payment Request</h3>
          <p class="text-xs text-slate-600">After transferring funds externally, enter your UTR/transaction reference and upload your payment screenshot for administrator approval.</p>

          <template x-if="manualFeedback">
            <div :class="manualFeedback.ok ? 'bg-emerald-50 border-emerald-200 text-[#16A36A]' : 'bg-rose-50 border-rose-200 text-rose-700'" class="p-3.5 rounded-xl border text-sm font-medium" x-text="manualFeedback.message"></div>
          </template>

          <form @submit.prevent="submitManualPayment" enctype="multipart/form-data" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount Paid (INR)</label>
                <input type="number" name="amount" step="0.01" min="<?= (float) $man['min_amount'] ?>" max="<?= (float) $man['max_amount'] ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num focus:outline-none focus:border-[#7138D8]">
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">UTR / Transaction Reference</label>
                <input type="text" name="utr_reference" required maxlength="120" placeholder="e.g. 421819304112" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono-num focus:outline-none focus:border-[#7138D8]">
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Screenshot (JPG, PNG, WEBP, max 3MB)</label>
              <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-[#F8F9FC]">
            </div>
            <button type="submit" :disabled="manualLoading" class="px-6 py-2.5 rounded-xl bg-[#7138D8] text-white text-sm font-semibold hover:bg-[#5B24C0] disabled:opacity-50 transition">
              <span x-text="manualLoading ? 'Submitting Request...' : 'Submit Payment For Approval'"></span>
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Payment History -->
  <div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="text-base font-bold text-[#151515] mb-4">Payment History</h2>
    <?php if (empty($paymentHistory)): ?>
      <p class="text-sm text-slate-500">No deposits recorded yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
              <th class="py-3 pr-4">Reference</th>
              <th class="py-3 px-4">Method</th>
              <th class="py-3 px-4">Details</th>
              <th class="py-3 px-4 text-right">Amount</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 pl-4 text-right">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <?php foreach ($paymentHistory as $p): ?>
              <tr>
                <td class="py-3 pr-4 font-mono-num text-xs font-semibold">#<?= e($p['payment_code']) ?></td>
                <td class="py-3 px-4 uppercase text-xs font-semibold text-slate-700"><?= e($p['method_code']) ?></td>
                <td class="py-3 px-4 text-xs text-slate-600 font-mono-num">
                  <?= e($p['razorpay_payment_id'] ?: ($p['utr_reference'] ?: '—')) ?>
                  <?php if (!empty($p['admin_note'])): ?>
                    <span class="block text-[11px] text-slate-500 font-sans mt-0.5"><?= e($p['admin_note']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-right font-mono-num font-semibold text-[#151515]"><?= e(format_currency($p['amount'])) ?></td>
                <td class="py-3 px-4"><?= payment_status_badge($p['status']) ?></td>
                <td class="py-3 pl-4 text-right text-xs text-slate-500 whitespace-nowrap"><?= e(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/user-footer.php'; ?>
