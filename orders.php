<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();

$pdo = Database::getConnection();
$statusFilter = sanitize_string($_GET['status'] ?? '', 30);
$search = sanitize_string($_GET['q'] ?? '', 120);

$conds = ['o.user_id = ?'];
$params = [(int) $user['id']];

if ($statusFilter !== '' && array_key_exists($statusFilter, ORDER_STATUSES)) {
    $conds[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $conds[] = '(o.order_code LIKE ? OR o.link LIKE ? OR s.name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT o.*, s.name AS service_name, s.icon_key AS service_icon, c.icon_key AS category_icon
        FROM orders o
        INNER JOIN services s ON s.id = o.service_id
        INNER JOIN categories c ON c.id = s.category_id
        WHERE ' . implode(' AND ', $conds) . '
        ORDER BY o.created_at DESC
        LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

Theme::renderPage('orders.php', [
    'pageTitle'    => 'My Orders',
    'user'         => $user,
    'orders'       => $orders,
    'statusFilter' => $statusFilter,
    'search'       => $search,
]);
