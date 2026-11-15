<?php
declare(strict_types=1);

class Ticket
{
    public static function create(int $userId, string $subject, string $category, string $priority, string $message, ?int $orderId = null, ?string $attachmentPath = null): array
    {
        $pdo = Database::getConnection();
        $code = 'TKT' . strtoupper(bin2hex(random_bytes(3)));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO support_tickets (ticket_code, user_id, subject, category, order_id, priority, status)
                 VALUES (?, ?, ?, ?, ?, ?, "open")'
            );
            $stmt->execute([$code, $userId, $subject, $category, $orderId, $priority]);
            $ticketId = (int) $pdo->lastInsertId();

            $msgStmt = $pdo->prepare(
                'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, attachment_path)
                 VALUES (?, "user", ?, ?, ?)'
            );
            $msgStmt->execute([$ticketId, $userId, $message, $attachmentPath]);

            $pdo->commit();
            log_activity('user', $userId, 'ticket_created', 'support_ticket', $ticketId, "Code: {$code}");
            return ['ok' => true, 'ticket_id' => $ticketId, 'ticket_code' => $code];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function reply(int $ticketId, string $senderType, int $senderId, string $message, ?string $attachmentPath = null): array
    {
        $pdo = Database::getConnection();
        $newStatus = $senderType === 'admin' ? 'answered' : 'customer_reply';
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, attachment_path)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$ticketId, $senderType, $senderId, $message, $attachmentPath]);

            $upd = $pdo->prepare('UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newStatus, $ticketId]);
            $pdo->commit();

            if ($senderType === 'admin') {
                $t = $pdo->prepare('SELECT user_id, ticket_code FROM support_tickets WHERE id = ?');
                $t->execute([$ticketId]);
                if ($row = $t->fetch()) {
                    create_notification((int) $row['user_id'], 'Ticket Reply', "Support replied to ticket #{$row['ticket_code']}.", 'info', 'ticket-details.php?id=' . $ticketId);
                }
            }
            return ['ok' => true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
