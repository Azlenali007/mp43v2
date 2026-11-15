<?php
declare(strict_types=1);
Theme::renderLayoutPart('dashboard', 'footer', [
    'pageScripts' => $pageScripts ?? [],
    'user'        => $user ?? current_user(),
]);
