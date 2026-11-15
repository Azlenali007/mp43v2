<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $newKey = User::regenerateApiKey((int) $user['id']);
    $user['api_key'] = $newKey;
    set_flash('success', 'A new API key has been generated.');
    redirect('api-access.php');
}

$endpointUrl = app_base_url() . '/api/v2.php';

Theme::renderPage('api-access.php', [
    'pageTitle'   => 'API Access',
    'user'        => $user,
    'endpointUrl' => $endpointUrl,
]);
