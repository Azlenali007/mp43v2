<?php
declare(strict_types=1);
Theme::renderLayoutPart('main', 'header', [
    'pageTitle' => $pageTitle ?? get_setting('site_name', 'CrownSMM'),
    'user'      => $user ?? current_user(),
]);
