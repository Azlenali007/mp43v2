<?php
declare(strict_types=1);

$docRoot = realpath(__DIR__ . '/smm-panel');
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$decodedPath = rawurldecode($uriPath);

// Block sensitive directories matching .htaccess protections
if (preg_match('#^/(config|database|storage|classes|includes)(/|$)#i', $decodedPath)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

// Block PHP execution inside uploads directory
if (preg_match('#^/uploads/.*\.php$#i', $decodedPath)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

// Block dotfiles
if (preg_match('#/\.#', $decodedPath)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

$target = $docRoot . $decodedPath;

if ($decodedPath === '/' || $decodedPath === '') {
    $indexFile = $docRoot . '/index.php';
    chdir($docRoot);
    $_SERVER['SCRIPT_FILENAME'] = $indexFile;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require $indexFile;
    return true;
}

if (is_dir($target)) {
    $indexFile = rtrim($target, '/') . '/index.php';
    if (is_file($indexFile)) {
        chdir(dirname($indexFile));
        $_SERVER['SCRIPT_FILENAME'] = $indexFile;
        $_SERVER['SCRIPT_NAME'] = rtrim($decodedPath, '/') . '/index.php';
        require $indexFile;
        return true;
    }
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

$realTarget = realpath($target);
if ($realTarget !== false && str_starts_with($realTarget, $docRoot) && is_file($realTarget)) {
    if (str_ends_with(strtolower($realTarget), '.php')) {
        chdir(dirname($realTarget));
        $_SERVER['SCRIPT_FILENAME'] = $realTarget;
        $_SERVER['SCRIPT_NAME'] = $decodedPath;
        require $realTarget;
        return true;
    }
    return false; // Let PHP built-in server serve static assets
}

// Clean extensionless routes fallback (e.g., /login -> /login.php)
$phpFallback = realpath($target . '.php');
if ($phpFallback !== false && str_starts_with($phpFallback, $docRoot) && is_file($phpFallback)) {
    chdir(dirname($phpFallback));
    $_SERVER['SCRIPT_FILENAME'] = $phpFallback;
    $_SERVER['SCRIPT_NAME'] = $decodedPath . '.php';
    require $phpFallback;
    return true;
}

http_response_code(404);
echo '404 Not Found';
return true;
