<?php
declare(strict_types=1);
if (class_exists('Theme')) {
    $themePart = Theme::componentPath('components/public-header.php');
    if ($themePart && is_file($themePart)) {
        require $themePart;
        return;
    }
}
require __DIR__ . '/../themes/classic/components/public-header.php';
