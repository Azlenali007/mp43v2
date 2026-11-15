<?php
declare(strict_types=1);

class Service
{
    public static function calculateSellingPrice(float $providerRate, string $marginType, float $marginValue): float
    {
        if ($marginType === 'fixed') {
            return round(max(0.01, $providerRate + $marginValue), 4);
        }
        // Percentage margin: e.g. 100 + (100 * 30 / 100) = 130
        return round(max(0.01, $providerRate + ($providerRate * $marginValue / 100.0)), 4);
    }

    public static function all(?int $categoryId = null, bool $onlyActive = false, string $search = ''): array
    {
        $pdo = Database::getConnection();
        $conds = [];
        $params = [];

        if ($onlyActive) {
            $conds[] = 's.status = "active" AND c.status = "active"';
        }
        if ($categoryId !== null && $categoryId > 0) {
            $conds[] = 's.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $conds[] = '(s.name LIKE ? OR s.description LIKE ? OR c.name LIKE ? OR CAST(s.id AS CHAR) = ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $search;
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT s.*, c.name AS category_name, c.icon_key AS category_icon, c.platform AS category_platform,
                       p.name AS provider_name
                FROM services s
                INNER JOIN categories c ON c.id = s.category_id
                LEFT JOIN providers p ON p.id = s.provider_id
                $where
                ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT s.*, c.name AS category_name, c.icon_key AS category_icon, c.status AS category_status
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO services (category_id, provider_id, provider_service_id, name, description, icon_key, platform, type,
                                   provider_rate, margin_type, margin_value, price_per_1000, min_qty, max_qty,
                                   dripfeed, refill, cancel, sync_price, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $data['category_id'],
            !empty($data['provider_id']) ? (int) $data['provider_id'] : null,
            $data['provider_service_id'] ?: null,
            $data['name'],
            $data['description'] ?? null,
            $data['icon_key'] ?: detect_icon_key_from_text($data['name']),
            $data['platform'] ?: detect_icon_key_from_text($data['name']),
            $data['type'] ?? 'default',
            (float) ($data['provider_rate'] ?? 0),
            $data['margin_type'] ?? 'percent',
            (float) ($data['margin_value'] ?? 0),
            (float) $data['price_per_1000'],
            (int) $data['min_qty'],
            (int) $data['max_qty'],
            !empty($data['dripfeed']) ? 1 : 0,
            !empty($data['refill']) ? 1 : 0,
            !empty($data['cancel']) ? 1 : 0,
            isset($data['sync_price']) ? (int) (bool) $data['sync_price'] : 1,
            (int) ($data['sort_order'] ?? 0),
            ($data['status'] ?? 'active') === 'disabled' ? 'disabled' : 'active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE services SET category_id = ?, provider_id = ?, provider_service_id = ?, name = ?, description = ?,
             icon_key = ?, platform = ?, type = ?, provider_rate = ?, margin_type = ?, margin_value = ?, price_per_1000 = ?,
             min_qty = ?, max_qty = ?, dripfeed = ?, refill = ?, cancel = ?, sync_price = ?, sort_order = ?, status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            (int) $data['category_id'],
            !empty($data['provider_id']) ? (int) $data['provider_id'] : null,
            $data['provider_service_id'] ?: null,
            $data['name'],
            $data['description'] ?? null,
            $data['icon_key'] ?: 'sparkles',
            $data['platform'] ?: 'general',
            $data['type'] ?? 'default',
            (float) ($data['provider_rate'] ?? 0),
            $data['margin_type'] ?? 'percent',
            (float) ($data['margin_value'] ?? 0),
            (float) $data['price_per_1000'],
            (int) $data['min_qty'],
            (int) $data['max_qty'],
            !empty($data['dripfeed']) ? 1 : 0,
            !empty($data['refill']) ? 1 : 0,
            !empty($data['cancel']) ? 1 : 0,
            isset($data['sync_price']) ? (int) (bool) $data['sync_price'] : 1,
            (int) ($data['sort_order'] ?? 0),
            ($data['status'] ?? 'active') === 'disabled' ? 'disabled' : 'active',
            $id,
        ]);
    }
}
