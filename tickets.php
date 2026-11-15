<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/Ticket.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $subject = sanitize_string($_POST['subject'] ?? '', 200);
    $category = in_array($_POST['category'] ?? '', ['order', 'payment', 'api', 'general'], true) ? $_POST['category'] : 'general';
    $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high'], true) ? $_POST['priority'] : 'medium';
    $orderId = validate_positive_int($_POST['order_id'] ?? null);
    $message = sanitize_string($_POST['message'] ?? '', 3000);

    if ($subject === '' || $message === '') {
        $error = 'Subject and message are required.';
    } else {
        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $up = validate_and_store_upload($_FILES['attachment'], 'tickets');
            if (!$up['ok']) {
                $error = $up['error'];
            } else {
                $attachment = $up['path'];
            }
        }
        if ($error === '') {
            $res = Ticket::create((int) $user['id'], $subject, $category, $priority, $message, $orderId, $attachment);
            if ($res['ok']) {
                set_flash('success', "Support ticket #{$res['ticket_code']} opened.");
                redirect('ticket-details.php?id=' . $res['ticket_id']);
            }
            $error = $res['error'];
        }
    }
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([(int) $user['id']]);
$tickets = $stmt->fetchAll();

Theme::renderPage('tickets.php', [
    'pageTitle' => 'Support Tickets',
    'user'      => $user,
    'tickets'   => $tickets,
    'error'     => $error,
]);
