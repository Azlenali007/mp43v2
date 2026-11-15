<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$user = require_user();
require_once __DIR__ . '/classes/Ticket.php';

$ticketId = validate_positive_int($_GET['id'] ?? null);
if (!$ticketId) {
    redirect('tickets.php');
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$ticketId, (int) $user['id']]);
$ticket = $stmt->fetch();
if (!$ticket) {
    set_flash('error', 'Ticket not found.');
    redirect('tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket['status'] !== 'closed') {
    verify_csrf_or_abort();
    $message = sanitize_string($_POST['message'] ?? '', 3000);
    if ($message !== '') {
        Ticket::reply($ticketId, 'user', (int) $user['id'], $message);
        set_flash('success', 'Reply sent.');
        redirect('ticket-details.php?id=' . $ticketId);
    }
}

$msgStmt = $pdo->prepare('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC');
$msgStmt->execute([$ticketId]);
$messages = $msgStmt->fetchAll();

Theme::renderPage('ticket-details.php', [
    'pageTitle' => 'Ticket #' . $ticket['ticket_code'],
    'user'      => $user,
    'ticket'    => $ticket,
    'messages'  => $messages,
]);
