<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Wallet.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/Razorpay.php';

function get_enabled_payment_methods_for_user(): array
{
    $methods = Payment::getMethods(true);
    $result = [];
    foreach ($methods as $m) {
        $cfg = json_decode((string) ($m['config_json'] ?? '{}'), true) ?: [];
        if ($m['code'] === 'razorpay') {
            $keyId = trim((string) ($cfg['key_id'] ?? ''));
            $keySecret = decrypt_secret((string) ($cfg['key_secret'] ?? ''));
            if ($keyId === '' || $keySecret === '') {
                // Only expose Razorpay if valid credentials are configured
                continue;
            }
            $result['razorpay'] = [
                'code'        => 'razorpay',
                'name'        => $m['name'],
                'description' => $m['description'],
                'mode'        => $m['mode'],
                'key_id'      => $keyId, // Public Key ID only — NEVER expose secrets!
                'min_amount'  => (float) $m['min_amount'],
                'max_amount'  => (float) $m['max_amount'],
            ];
        } elseif ($m['code'] === 'manual') {
            $result['manual'] = [
                'code'           => 'manual',
                'name'           => $cfg['method_name'] ?? $m['name'],
                'description'    => $m['description'],
                'min_amount'     => (float) $m['min_amount'],
                'max_amount'     => (float) $m['max_amount'],
                'upi_id'         => (string) ($cfg['upi_id'] ?? ''),
                'qr_code_path'   => (string) ($cfg['qr_code_path'] ?? ''),
                'bank_name'      => (string) ($cfg['bank_name'] ?? ''),
                'account_number' => (string) ($cfg['account_number'] ?? ''),
                'account_holder' => (string) ($cfg['account_holder'] ?? ''),
                'ifsc_code'      => (string) ($cfg['ifsc_code'] ?? ''),
                'instructions'   => (string) ($cfg['instructions'] ?? ''),
            ];
        }
    }
    return $result;
}
