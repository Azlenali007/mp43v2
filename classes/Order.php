<?php
declare(strict_types=1);

class Order
{
    public static function placeOrder(int $userId, int $serviceId, string $link, int $quantity, string $placedVia = 'web'): array
    {
        if (!is_valid_url($link)) {
            return ['ok' => false, 'error' => 'Please enter a valid target URL (https://...) or @handle.'];
        }

        $pdo = Database::getConnection();
        $service = Service::find($serviceId);
        if (!$service || $service['status'] !== 'active' || $service['category_status'] !== 'active') {
            return ['ok' => false, 'error' => 'Selected service is not available.'];
        }

        $minQty = (int) $service['min_qty'];
        $maxQty = (int) $service['max_qty'];
        if ($quantity < $minQty || $quantity > $maxQty) {
            return ['ok' => false, 'error' => "Quantity must be between {$minQty} and {$maxQty}."];
        }

        // Canonical base currency calculation
        $rate = (float) $service['price_per_1000'];
        $providerRate = (float) $service['provider_rate'];
        $charge = round(($quantity / 1000.0) * $rate, 4);
        $cost = round(($quantity / 1000.0) * $providerRate, 4);
        $profit = round($charge - $cost, 4);

        if ($charge <= 0) {
            return ['ok' => false, 'error' => 'Invalid calculated order amount.'];
        }

        // Snapshot user display currency at order creation time
        $uStmt = $pdo->prepare('SELECT preferred_currency FROM users WHERE id = ? LIMIT 1');
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch() ?: [];
        $baseCode = Currency::getBaseCode();
        $displayCur = Currency::getUserDisplay($uRow);
        $displayCode = (string) $displayCur['code'];
        $exRate = 1.0;
        $displayedCharge = $charge;

        if ($displayCode !== $baseCode) {
            $conv = Currency::convertStrict($charge, $baseCode, $displayCode, 4);
            if ($conv['ok']) {
                $exRate = (float) $conv['cross_rate'];
                $displayedCharge = (float) $conv['amount'];
            } else {
                $displayCode = $baseCode;
            }
        }

        $orderCode = 'ORD' . strtoupper(bin2hex(random_bytes(4)));

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO orders
                 (order_code, user_id, service_id, provider_id, link, quantity, charge, cost, profit,
                  base_currency, display_currency, exchange_rate, displayed_charge, remains, status, placed_via)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $orderCode,
                $userId,
                $serviceId,
                $service['provider_id'] ? (int) $service['provider_id'] : null,
                $link,
                $quantity,
                $charge,
                $cost,
                $profit,
                $baseCode,
                $displayCode,
                $exRate,
                $displayedCharge,
                $quantity,
                'pending',
                $placedVia,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $debit = Wallet::debit(
                $userId,
                $charge,
                'order',
                $orderId,
                "Order #{$orderCode} - {$service['name']} (Qty: {$quantity})",
                'order_debit_' . $orderCode,
                null,
                true,
                [
                    'original_amount'   => $displayedCharge,
                    'original_currency' => $displayCode,
                    'display_currency'  => $displayCode,
                    'exchange_rate'     => $exRate,
                    'displayed_amount'  => $displayedCharge,
                ]
            );
            if (!$debit['ok']) {
                throw new RuntimeException($debit['error']);
            }

            self::addLog($orderId, 'pending', 'Order created and wallet debited (' . Currency::formatExact($displayedCharge, $displayCode) . ' / ' . Currency::formatExact($charge, $baseCode) . ').');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($displayCode !== $baseCode) {
            Currency::logConversion('order_place', $baseCode, $displayCode, $charge, $displayedCharge, $exRate, $orderId);
        }

        // Dispatch to external provider API if linked
        if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
            $provRes = Provider::submitOrder(
                (int) $service['provider_id'],
                (string) $service['provider_service_id'],
                $link,
                $quantity
            );
            if ($provRes['ok']) {
                $upd = $pdo->prepare('UPDATE orders SET provider_order_id = ?, status = "processing" WHERE id = ?');
                $upd->execute([$provRes['provider_order_id'], $orderId]);
                self::addLog($orderId, 'processing', 'Submitted to provider. Provider Order ID: ' . $provRes['provider_order_id'], json_encode($provRes['raw']));
            } else {
                self::addLog($orderId, 'pending', 'Provider dispatch queued/deferred: ' . $provRes['error']);
            }
        }

        log_activity('user', $userId, 'order_placed', 'order', $orderId, "Code: {$orderCode}, Qty: {$quantity}, BaseCharge: {$charge} {$baseCode}");

        return [
            'ok'               => true,
            'order_id'         => $orderId,
            'order_code'       => $orderCode,
            'charge'           => $charge,
            'base_currency'    => $baseCode,
            'display_currency' => $displayCode,
            'displayed_charge' => $displayedCharge,
        ];
    }

    public static function mapProviderStatus(string $externalStatus): string
    {
        $norm = strtolower(trim($externalStatus));
        return match ($norm) {
            'pending'                    => 'pending',
            'processing'                 => 'processing',
            'in progress', 'in_progress' => 'in_progress',
            'completed', 'complete'      => 'completed',
            'partial'                    => 'partial',
            'canceled', 'cancelled'      => 'cancelled',
            'fail', 'failed', 'error'    => 'failed',
            default                      => 'processing',
        };
    }

    public static function syncOrderWithProvider(int $orderId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order || empty($order['provider_id']) || empty($order['provider_order_id'])) {
            return ['ok' => false, 'error' => 'Order is not linked to a provider order ID.'];
        }

        $res = Provider::fetchOrderStatus((int) $order['provider_id'], (string) $order['provider_order_id']);
        if (!$res['ok']) {
            return $res;
        }

        $data = $res['data'];
        $newStatus = isset($data['status']) ? self::mapProviderStatus((string) $data['status']) : $order['status'];
        $startCount = isset($data['start_count']) ? (int) $data['start_count'] : (int) $order['start_count'];
        $remains = isset($data['remains']) ? max(0, (int) $data['remains']) : (int) $order['remains'];

        $upd = $pdo->prepare('UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?');
        $upd->execute([$newStatus, $startCount, $remains, $orderId]);
        self::addLog($orderId, $newStatus, "Synced from provider (Start: {$startCount}, Remains: {$remains})", json_encode($data));

        if (in_array($newStatus, ['cancelled', 'failed'], true) && (float) $order['refunded_amount'] <= 0) {
            self::refundOrder($orderId, (float) $order['charge'], 'Automatic refund for ' . $newStatus . ' order');
        } elseif ($newStatus === 'partial' && $remains > 0 && (int) $order['quantity'] > 0 && (float) $order['refunded_amount'] <= 0) {
            $ratio = min(1.0, $remains / (int) $order['quantity']);
            $partialRefund = round((float) $order['charge'] * $ratio, 4);
            if ($partialRefund > 0) {
                self::refundOrder($orderId, $partialRefund, "Partial refund for {$remains} remaining quantity");
            }
        }

        return ['ok' => true, 'status' => $newStatus, 'start_count' => $startCount, 'remains' => $remains];
    }

    public static function refundOrder(int $orderId, float $amount, string $reason, ?int $adminId = null): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Refund amount must be positive.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $lock->execute([$orderId]);
            $order = $lock->fetch();
            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            $maxRefundable = round((float) $order['charge'] - (float) $order['refunded_amount'], 4);
            if ($maxRefundable <= 0) {
                $pdo->commit();
                return ['ok' => false, 'error' => 'Order has already been fully refunded.'];
            }

            $actualRefund = min($amount, $maxRefundable);
            $newRefunded = round((float) $order['refunded_amount'] + $actualRefund, 4);
            $newStatus = ($newRefunded + 0.0001 >= (float) $order['charge']) ? 'refunded' : $order['status'];

            $orderDispCur = (string) ($order['display_currency'] ?? Currency::getBaseCode());
            $orderExRate = (float) ($order['exchange_rate'] ?? 1.0);
            $dispRefund = round($actualRefund * $orderExRate, 4);

            $credit = Wallet::credit(
                (int) $order['user_id'],
                $actualRefund,
                'order_refund',
                $orderId,
                "Refund for Order #{$order['order_code']}: {$reason}",
                'refund_order_' . $orderId,
                $adminId,
                'refund',
                [
                    'original_amount'   => $dispRefund,
                    'original_currency' => $orderDispCur,
                    'display_currency'  => $orderDispCur,
                    'exchange_rate'     => $orderExRate,
                    'displayed_amount'  => $dispRefund,
                ]
            );
            if (!$credit['ok']) {
                throw new RuntimeException($credit['error']);
            }

            $upd = $pdo->prepare('UPDATE orders SET refunded_amount = ?, status = ? WHERE id = ?');
            $upd->execute([$newRefunded, $newStatus, $orderId]);
            self::addLog($orderId, $newStatus, 'Refunded ' . Currency::formatExact($actualRefund, Currency::getBaseCode()) . ': ' . $reason);

            $pdo->commit();
            create_notification((int) $order['user_id'], 'Order Refunded', "Order #{$order['order_code']} refunded " . Currency::formatExact($dispRefund, $orderDispCur), 'info', 'order-details.php?id=' . $orderId);
            return ['ok' => true, 'refunded' => $actualRefund];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function addLog(int $orderId, string $status, string $message, ?string $rawResponse = null): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('INSERT INTO order_logs (order_id, status, message, raw_response) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $status, substr($message, 0, 500), $rawResponse]);
        } catch (Throwable $e) {
        }
    }
}
