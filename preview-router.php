<?php
declare(strict_types=1);

$docRoot = realpath(__DIR__);
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve static assets directly if they exist
$target = $docRoot . $uriPath;
if ($uriPath !== '/' && is_file($target) && !str_ends_with(strtolower($target), '.php')) {
    return false;
}

// Otherwise delegate to central router.php
require_once __DIR__ . '/router.php';
return true;
