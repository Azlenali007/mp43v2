<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensure_installed();

require_once __DIR__ . '/classes/Ticket.php';

$user = current_user();
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    if (!check_rate_limit('contact_form', 6, 600)) {
        $errorMsg = 'Too many messages sent recently. Please wait a few minutes.';
    } else {
        $name = sanitize_string($_POST['name'] ?? ($user['name'] ?? ''), 120);
        $email = sanitize_string($_POST['email'] ?? ($user['email'] ?? ''), 190);
        $subject = sanitize_string($_POST['subject'] ?? '', 200);
        $message = sanitize_string($_POST['message'] ?? '', 2000);

        if ($name === '' || !is_valid_email($email) || $subject === '' || $message === '') {
            $errorMsg = 'Please fill in all required fields with a valid email address.';
        } else {
            if ($user) {
                $tRes = Ticket::create((int) $user['id'], $subject, 'general', 'medium', $message);
                if ($tRes['ok']) {
                    $successMsg = "Your message has been logged as Ticket #{$tRes['ticket_code']}. Our team will reply shortly.";
                } else {
                    $errorMsg = $tRes['error'];
                }
            } else {
                log_activity('system', null, 'public_contact_inquiry', null, null, "From: {$name} <{$email}> | Subject: {$subject} | Message: {$message}");
                $successMsg = 'Thank you for contacting us. Your inquiry has been received by our support team.';
            }
        }
    }
}

Theme::renderPage('contact.php', [
    'pageTitle'  => 'Contact Us',
    'user'       => $user,
    'successMsg' => $successMsg,
    'errorMsg'   => $errorMsg,
]);
