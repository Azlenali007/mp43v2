<?php
declare(strict_types=1);
define('APP_SUBDIR_PREFIX', '../');
require_once __DIR__ . '/../includes/auth.php';
ensure_installed();
if (current_user()) {
    redirect('dashboard.php');
}
redirect('../login.php');
