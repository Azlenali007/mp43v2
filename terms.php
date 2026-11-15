<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

Theme::renderPage('terms.php', [
    'pageTitle' => 'Terms & Conditions',
]);
