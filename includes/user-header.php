<?php
declare(strict_types=1);
Theme::renderLayoutPart('dashboard', 'header', [
    'pageTitle' => $pageTitle ?? 'Dashboard',
    'user'      => $user ?? current_user(),
]);
