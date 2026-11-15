<?php
declare(strict_types=1);
Theme::renderLayoutPart('main', 'footer', [
    'pageScripts' => $pageScripts ?? [],
    'user'        => $user ?? current_user(),
]);
