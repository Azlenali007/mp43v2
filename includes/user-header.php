<?php
declare(strict_types=1);
if (class_exists('Theme')) {
    $themePart = Theme::componentPath('components/user-header.php');
    if ($themePart && is_file($themePart)) {
        require $themePart;
        return;
    }
}
require __DIR__ . '/../themes/classic/components/user-header.php';
