<?php
declare(strict_types=1);
if (class_exists('Theme')) {
    $themePart = Theme::componentPath('components/public-footer.php');
    if ($themePart && is_file($themePart)) {
        require $themePart;
        return;
    }
}
require __DIR__ . '/../themes/classic/components/public-footer.php';
