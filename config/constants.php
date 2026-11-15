<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('CONFIG_PATH', APP_ROOT . '/config');
define('INCLUDES_PATH', APP_ROOT . '/includes');
define('CLASSES_PATH', APP_ROOT . '/classes');
define('UPLOADS_PATH', APP_ROOT . '/uploads');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');
define('INSTALL_LOCK_FILE', CONFIG_PATH . '/installed.lock');

define('ORDER_STATUSES', [
    'pending'     => 'Pending',
    'processing'  => 'Processing',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
    'partial'     => 'Partial',
    'cancelled'   => 'Cancelled',
    'failed'      => 'Failed',
    'refunded'    => 'Refunded',
]);

define('PAYMENT_STATUSES', [
    'created'   => 'Created',
    'pending'   => 'Pending',
    'paid'      => 'Paid',
    'failed'    => 'Failed',
    'cancelled' => 'Cancelled',
    'rejected'  => 'Rejected',
]);

define('TICKET_STATUSES', [
    'open'           => 'Open',
    'answered'       => 'Answered',
    'customer_reply' => 'Customer Reply',
    'closed'         => 'Closed',
]);

define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB
define('ALLOWED_IMAGE_MIMES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);
