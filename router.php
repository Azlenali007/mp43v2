<?php
declare(strict_types=1);

$docRoot = realpath(__DIR__);
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$decodedPath = rawurldecode($uriPath);

// Block direct web access to sensitive folders
if (preg_match('#^/(config|database|storage|classes|includes)(/|$)#i', $decodedPath)) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

// Block internal files and dotfiles
if (preg_match('#^/(preview-router\.php|router\.php|package\.json|metadata\.json|\.env|\.git)$#i', $decodedPath) || preg_match('#/\.#', $decodedPath)) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

// Block PHP execution inside uploads directory
if (preg_match('#^/uploads/.*\.php$#i', $decodedPath)) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

// Map direct .php requests to clean URL 301 redirects (excluding api/ and install/)
if (str_ends_with(strtolower($decodedPath), '.php') && !str_starts_with($decodedPath, '/api/') && !str_starts_with($decodedPath, '/install/')) {
    $cleanRoute = rtrim(substr($decodedPath, 0, -4), '/');
    if ($cleanRoute === '' || $cleanRoute === '/index') {
        $cleanRoute = '/';
    }
    header('Location: ' . $cleanRoute, true, 301);
    exit;
}

// Clean route whitelist mapping
$routes = [
    '/'                  => '/index.php',
    '/index'             => '/index.php',
    '/login'             => '/login.php',
    '/register'          => '/register.php',
    '/logout'            => '/logout.php',
    '/dashboard'         => '/dashboard.php',
    '/services'          => '/services.php',
    '/new-order'         => '/new-order.php',
    '/bulk-order'        => '/bulk-order.php',
    '/orders'            => '/orders.php',
    '/order-details'     => '/order-details.php',
    '/add-funds'         => '/add-funds.php',
    '/transactions'      => '/transactions.php',
    '/tickets'           => '/tickets.php',
    '/ticket-details'    => '/ticket-details.php',
    '/profile'           => '/profile.php',
    '/api-access'        => '/api-access.php',
    '/faq'               => '/faq.php',
    '/contact'           => '/contact.php',
    '/terms'             => '/terms.php',
    '/privacy'           => '/privacy.php',
    '/user/dashboard'    => '/user/dashboard.php',
    '/tools'             => '/tools/index.php',
    '/admin/login'       => '/admin/login.php',
    '/admin/dashboard'   => '/admin/dashboard.php',
    '/admin/users'       => '/admin/users.php',
    '/admin/services'    => '/admin/services.php',
    '/admin/categories'  => '/admin/categories.php',
    '/admin/providers'   => '/admin/providers.php',
    '/admin/orders'      => '/admin/orders.php',
    '/admin/payments'    => '/admin/payments.php',
    '/admin/payment-methods' => '/admin/payment-methods.php',
    '/admin/currencies'  => '/admin/currencies.php',
    '/admin/themes'      => '/admin/themes.php',
    '/admin/tools'       => '/admin/tools.php',
    '/admin/settings'    => '/admin/settings.php',
    '/admin/admins'      => '/admin/admins.php',
    '/admin/security'    => '/admin/security.php',
];

$normPath = rtrim($decodedPath, '/') ?: '/';
if (isset($routes[$normPath])) {
    $scriptPath = $docRoot . $routes[$normPath];
    if (is_file($scriptPath)) {
        chdir(dirname($scriptPath));
        $_SERVER['SCRIPT_FILENAME'] = $scriptPath;
        $_SERVER['SCRIPT_NAME'] = $routes[$normPath];
        require $scriptPath;
        return true;
    }
}

// Fallback for static assets or direct api/install scripts
$target = $docRoot . $decodedPath;
if (is_file($target)) {
    if (str_ends_with(strtolower($target), '.php')) {
        chdir(dirname($target));
        $_SERVER['SCRIPT_FILENAME'] = $target;
        $_SERVER['SCRIPT_NAME'] = $decodedPath;
        require $target;
        return true;
    }
    return false; // let web server serve static asset
}

// 404 Not Found
http_response_code(404);
$err404 = $docRoot . '/404.php';
if (is_file($err404)) {
    include $err404;
} else {
    echo '<!DOCTYPE html><html lang="en"><head><title>404 Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:50px"><h1>404 Not Found</h1><p>The requested page could not be found.</p><a href="/">Return Home</a></body></html>';
}
exit;
