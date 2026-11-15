<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Service.php';

$user = current_user();
$selectedCategoryId = validate_positive_int($_GET['category'] ?? null);
$search = sanitize_string($_GET['q'] ?? '', 120);

$categories = Category::all(true);
$services = Service::all($selectedCategoryId, true, $search);

Theme::renderPage('services.php', [
    'pageTitle'          => 'Services Catalog',
    'user'               => $user,
    'selectedCategoryId' => $selectedCategoryId,
    'search'             => $search,
    'categories'         => $categories,
    'services'           => $services,
]);
