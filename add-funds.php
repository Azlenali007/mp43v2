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

$defaultTab = isset($enabledMethods['razorpay']) ? 'razorpay' : (isset($enabledMethods['manual']) ? 'manual' : '');

$displayCurrency = Currency::getUserDisplay($user);
$displayCode = (string) $displayCurrency['code'];
$displaySymbol = (string) $displayCurrency['symbol'];
$baseCurrency = Currency::getBase();
$baseCode = (string) $baseCurrency['code'];
$baseSymbol = (string) $baseCurrency['symbol'];

$rzpMinDisp = isset($enabledMethods['razorpay']) ? Currency::convertFromBase((float) $enabledMethods['razorpay']['min_amount'], $displayCode, 2) : 0;
$rzpMaxDisp = isset($enabledMethods['razorpay']) ? Currency::convertFromBase((float) $enabledMethods['razorpay']['max_amount'], $displayCode, 2) : 100000;
$inrRateCheck = Currency::convertStrict(1, $displayCode, 'INR', 6);
$toInrFactor = $inrRateCheck['ok'] ? (float) $inrRateCheck['cross_rate'] : 1.0;

$manAcceptedCode = isset($enabledMethods['manual']) ? strtoupper((string) ($enabledMethods['manual']['accepted_currency'] ?? $baseCode)) : $baseCode;
$manAcceptedCur = Currency::find($manAcceptedCode) ?: $baseCurrency;
$manToBaseConv = Currency::convertStrict(1, $manAcceptedCode, $baseCode, 6);
$manToBaseFactor = $manToBaseConv['ok'] ? (float) $manToBaseConv['cross_rate'] : 1.0;
$manMinConv = isset($enabledMethods['manual']) ? Currency::convertStrict((float) $enabledMethods['manual']['min_amount'], $baseCode, $manAcceptedCode, 2) : ['ok' => true, 'amount' => 10];
$manMaxConv = isset($enabledMethods['manual']) ? Currency::convertStrict((float) $enabledMethods['manual']['max_amount'], $baseCode, $manAcceptedCode, 2) : ['ok' => true, 'amount' => 100000];
$manMinAmt = $manMinConv['ok'] ? (float) $manMinConv['amount'] : 1;
$manMaxAmt = $manMaxConv['ok'] ? (float) $manMaxConv['amount'] : 100000;

Theme::renderPage('add-funds.php', [
    'pageTitle'       => 'Add Funds',
    'user'            => $user,
    'enabledMethods'  => $enabledMethods,
    'paymentHistory'  => $paymentHistory,
    'defaultTab'      => $defaultTab,
    'displayCode'     => $displayCode,
    'displaySymbol'   => $displaySymbol,
    'baseCode'        => $baseCode,
    'baseSymbol'      => $baseSymbol,
    'rzpMinDisp'      => $rzpMinDisp,
    'rzpMaxDisp'      => $rzpMaxDisp,
    'toInrFactor'     => $toInrFactor,
    'manAcceptedCode' => $manAcceptedCode,
    'manAcceptedCur'  => $manAcceptedCur,
    'manToBaseFactor' => $manToBaseFactor,
    'manMinAmt'       => $manMinAmt,
    'manMaxAmt'       => $manMaxAmt,
]);
