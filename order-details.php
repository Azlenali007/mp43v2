<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$orderId = validate_positive_int($_GET['id'] ?? null);
if (!$orderId) {
    redirect('orders.php');
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    'SELECT o.*, s.name AS service_name, s.icon_key AS service_icon, c.name AS category_name
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     INNER JOIN categories c ON c.id = s.category_id
     WHERE o.id = ? AND o.user_id = ? LIMIT 1'
);
$stmt->execute([$orderId, (int) $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('orders.php');
}

$logsStmt = $pdo->prepare('SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC');
$logsStmt->execute([$orderId]);
$logs = $logsStmt->fetchAll();

Theme::renderPage('order-details.php', [
    'pageTitle' => 'Order #' . $order['order_code'],
    'user'      => $user,
    'order'     => $order,
    'logs'      => $logs,
]);
