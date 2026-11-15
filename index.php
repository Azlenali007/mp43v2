<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$pdo = Database::getConnection();
$categories = Category::all(true);
$popularServices = $pdo->query(
    'SELECT s.*, c.name AS category_name, c.icon_key AS category_icon
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.status = "active" AND c.status = "active"
     ORDER BY s.sort_order ASC, s.id ASC
     LIMIT 6'
)->fetchAll();

$totalServicesCount = (int) $pdo->query('SELECT COUNT(*) FROM services WHERE status = "active"')->fetchColumn();
$totalOrdersCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

Theme::renderPage('index.php', [
    'pageTitle'          => 'Social Media Marketing Panel',
    'categories'         => $categories,
    'popularServices'    => $popularServices,
    'totalServicesCount' => $totalServicesCount,
    'totalOrdersCount'   => $totalOrdersCount,
]);
