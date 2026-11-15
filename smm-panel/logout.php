<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/Auth.php';

Auth::logoutUser();
set_flash('info', 'You have been signed out.');
redirect('login.php');
