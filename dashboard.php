<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$pdo = Database::getConnection();
$userId = (int) $user['id'];

$range = ($_GET['range'] ?? '7d') === '30d' ? '30d' : '7d';
$days = $range === '30d' ? 30 : 7;

$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status IN ("pending","processing","in_progress") THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status IN ("cancelled","failed","refunded") THEN 1 ELSE 0 END) AS cancelled_orders,
        SUM(CASE WHEN status IN ("processing","in_progress") THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS strictly_pending
     FROM orders
     WHERE user_id = ?'
);
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch() ?: [];

$totalOrders = (int) ($stats['total_orders'] ?? 0);
$completedOrders = (int) ($stats['completed_orders'] ?? 0);
$pendingOrders = (int) ($stats['pending_orders'] ?? 0);
$processingCount = (int) ($stats['processing_orders'] ?? 0);
$strictlyPending = (int) ($stats['strictly_pending'] ?? 0);
$cancelledCount = (int) ($stats['cancelled_orders'] ?? 0);

$chartStmt = $pdo->prepare(
    'SELECT DATE(created_at) AS day_date, COALESCE(SUM(charge), 0) AS total_spend
     FROM orders
     WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY DATE(created_at)
     ORDER BY day_date ASC'
);
$chartStmt->execute([$userId, $days - 1]);
$rawChart = [];
foreach ($chartStmt->fetchAll() as $r) {
    $rawChart[$r['day_date']] = (float) $r['total_spend'];
}

$displayCurrency = Currency::getUserDisplay($user);
$displayCode = (string) $displayCurrency['code'];
$displayDecimals = (int) ($displayCurrency['decimal_places'] ?? 2);

$seriesLabels = [];
$seriesValues = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $seriesLabels[] = $days === 7 ? date('D', strtotime($d)) : date('d M', strtotime($d));
    $seriesValues[] = Currency::convertFromBase($rawChart[$d] ?? 0.0, $displayCode, $displayDecimals);
}

$recentStmt = $pdo->prepare(
    'SELECT o.*, s.name AS service_name, s.icon_key AS service_icon, c.icon_key AS category_icon
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     INNER JOIN categories c ON c.id = s.category_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC
     LIMIT 5'
);
$recentStmt->execute([$userId]);
$recentOrders = $recentStmt->fetchAll();

$announcements = $pdo->query(
    'SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4'
)->fetchAll();

$currencySymbol = (string) ($displayCurrency['symbol'] ?? get_setting('currency_symbol', '₹'));

Theme::renderPage('dashboard.php', [
    'pageTitle'       => 'Dashboard',
    'user'            => $user,
    'range'           => $range,
    'totalOrders'     => $totalOrders,
    'completedOrders' => $completedOrders,
    'pendingOrders'   => $pendingOrders,
    'processingCount' => $processingCount,
    'strictlyPending' => $strictlyPending,
    'cancelledCount'  => $cancelledCount,
    'seriesLabels'    => $seriesLabels,
    'seriesValues'    => $seriesValues,
    'recentOrders'    => $recentOrders,
    'announcements'   => $announcements,
    'currencySymbol'  => $currencySymbol,
]);
