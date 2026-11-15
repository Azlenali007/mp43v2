<?php
declare(strict_types=1);

class Category
{
    public static function all(bool $onlyActive = false): array
    {
        $pdo = Database::getConnection();
        $where = $onlyActive ? 'WHERE c.status = "active"' : '';
        $sql = "SELECT c.*, COUNT(s.id) AS services_count,
                       SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) AS active_services_count
                FROM categories c
                LEFT JOIN services s ON s.category_id = c.id
                $where
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.id ASC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $slug = slugify($data['name']);
        $check = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $stmt = $pdo->prepare(
            'INSERT INTO categories (name, slug, icon_key, platform, description, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $slug,
            $data['icon_key'] ?: detect_icon_key_from_text($data['name']),
            $data['platform'] ?: detect_icon_key_from_text($data['name']),
            $data['description'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            $data['status'] === 'disabled' ? 'disabled' : 'active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE categories SET name = ?, icon_key = ?, platform = ?, description = ?, sort_order = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['icon_key'] ?: 'globe',
            $data['platform'] ?: 'general',
            $data['description'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            $data['status'] === 'disabled' ? 'disabled' : 'active',
            $id,
        ]);
    }

    public static function deleteProtected(int $id): array
    {
        $pdo = Database::getConnection();
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM services WHERE category_id = ?');
        $countStmt->execute([$id]);
        $count = (int) $countStmt->fetchColumn();
        if ($count > 0) {
            return ['ok' => false, 'error' => "Cannot delete category: {$count} service(s) are still assigned to it. Move or delete those services first."];
        }
        $del = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $del->execute([$id]);
        return ['ok' => true];
    }
}
