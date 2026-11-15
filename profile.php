<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'update_profile') {
        try {
            $name = sanitize_string($_POST['name'] ?? '', 120);
            $phone = sanitize_string($_POST['phone'] ?? '', 30);
            $tz = sanitize_string($_POST['timezone'] ?? 'Asia/Kolkata', 64) ?: 'Asia/Kolkata';
            $rawCur = strtoupper(sanitize_string($_POST['preferred_currency'] ?? '', 10));

            if ($name === '') {
                set_flash('error', 'Full name is required.');
                redirect('profile.php');
            }

            $prefCur = null;
            if ($rawCur !== '') {
                $curRow = Currency::find($rawCur);
                if (!$curRow || (int) ($curRow['is_enabled'] ?? 0) !== 1) {
                    set_flash('error', "Currency '{$rawCur}' is not enabled on this platform.");
                    redirect('profile.php');
                }
                if ((float) ($curRow['exchange_rate'] ?? 0) <= 0) {
                    set_flash('error', "Currency '{$rawCur}' does not have a synced exchange rate yet. Please ask the administrator to sync currency rates.");
                    redirect('profile.php');
                }
                $prefCur = $rawCur;
            }

            $res = User::updateProfile((int) $user['id'], $name, $phone, $tz, $prefCur);
            if (!empty($res['ok'])) {
                $user = current_user(true) ?: $user;
                $savedCur = $prefCur ?? Currency::getBaseCode();
                set_flash('success', "Profile settings updated. Display currency set to {$savedCur}.");
            } else {
                set_flash('error', $res['error'] ?? 'Could not save profile changes. Please try again.');
            }
        } catch (Throwable $e) {
            write_app_log('profile', 'Profile update exception for user #' . (int) $user['id'] . ': ' . $e->getMessage());
            set_flash('error', 'An unexpected error occurred while saving your profile settings. Please try again.');
        }
        redirect('profile.php');
    } elseif ($action === 'change_password') {
        try {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            if (strlen($new) < 8) {
                set_flash('error', 'New password must be at least 8 characters.');
            } elseif ($new !== $confirm) {
                set_flash('error', 'New passwords do not match.');
            } else {
                $res = User::changePassword((int) $user['id'], $current, $new);
                set_flash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Password updated successfully.' : ($res['error'] ?? 'Unable to update password.'));
            }
        } catch (Throwable $e) {
            write_app_log('profile', 'Password update exception for user #' . (int) $user['id'] . ': ' . $e->getMessage());
            set_flash('error', 'Unable to update password right now. Please try again.');
        }
        redirect('profile.php');
    }
}

Theme::renderPage('profile.php', [
    'pageTitle' => 'Account Settings',
    'user'      => $user,
]);
