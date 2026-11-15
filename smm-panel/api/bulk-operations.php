<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Category.php';

ensure_installed();
$admin = current_admin();
if (!$admin) {
    json_response(['ok' => false, 'message' => 'Unauthorized.'], 403);
}
verify_csrf_or_abort();

$entity = $_POST['entity'] ?? '';
$action = $_POST['bulk_action'] ?? '';
$ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn($v) => $v > 0));

$pdo = Database::getConnection();

if ($entity === 'services' && admin_has_permission($admin, 'manage_services')) {
    if (empty($ids)) {
        set_flash('warning', 'Select at least one service first.');
        redirect('../admin/services.php');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'enable':
            $pdo->prepare("UPDATE services SET status = 'active' WHERE id IN ($placeholders)")->execute($ids);
            set_flash('success', count($ids) . ' service(s) enabled.');
            break;
        case 'disable':
            $pdo->prepare("UPDATE services SET status = 'disabled' WHERE id IN ($placeholders)")->execute($ids);
            set_flash('success', count($ids) . ' service(s) disabled.');
            break;
        case 'delete':
            $deleted = 0;
            $skipped = 0;
            $chk = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE service_id = ?');
            $del = $pdo->prepare('DELETE FROM services WHERE id = ?');
            foreach ($ids as $id) {
                $chk->execute([$id]);
                if ((int) $chk->fetchColumn() > 0) {
                    $skipped++;
                } else {
                    $del->execute([$id]);
                    $deleted++;
                }
            }
            set_flash($skipped > 0 ? 'warning' : 'success', "Deleted {$deleted} service(s)." . ($skipped > 0 ? " {$skipped} skipped (has existing orders; disable instead)." : ''));
            break;
        case 'price_adjust':
            $pct = (float) ($_POST['price_percent'] ?? 0);
            if ($pct != 0.0) {
                $factor = 1.0 + ($pct / 100.0);
                if ($factor > 0) {
                    $params = array_merge([$factor], $ids);
                    $pdo->prepare("UPDATE services SET price_per_1000 = ROUND(GREATEST(0.01, price_per_1000 * ?), 4) WHERE id IN ($placeholders)")->execute($params);
                    set_flash('success', "Adjusted prices by {$pct}% for " . count($ids) . ' service(s).');
                }
            }
            break;
        case 'move_category':
            $targetCat = validate_positive_int($_POST['target_category_id'] ?? null);
            if ($targetCat) {
                $params = array_merge([$targetCat], $ids);
                $pdo->prepare("UPDATE services SET category_id = ? WHERE id IN ($placeholders)")->execute($params);
                set_flash('success', count($ids) . ' service(s) moved to target category.');
            }
            break;
        case 'change_provider':
            $targetProv = !empty($_POST['target_provider_id']) ? validate_positive_int($_POST['target_provider_id']) : null;
            $params = array_merge([$targetProv], $ids);
            $pdo->prepare("UPDATE services SET provider_id = ? WHERE id IN ($placeholders)")->execute($params);
            set_flash('success', 'Updated provider assignment for ' . count($ids) . ' service(s).');
            break;
    }
    log_activity('admin', (int) $admin['id'], 'bulk_services_' . $action, 'service', null, 'IDs: ' . implode(',', $ids));
    redirect('../admin/services.php');
}

if ($entity === 'categories' && admin_has_permission($admin, 'manage_categories')) {
    if (empty($ids)) {
        set_flash('warning', 'Select at least one category first.');
        redirect('../admin/categories.php');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'enable':
            $pdo->prepare("UPDATE categories SET status = 'active' WHERE id IN ($placeholders)")->execute($ids);
            set_flash('success', count($ids) . ' category(s) enabled.');
            break;
        case 'disable':
            $pdo->prepare("UPDATE categories SET status = 'disabled' WHERE id IN ($placeholders)")->execute($ids);
            set_flash('success', count($ids) . ' category(s) disabled.');
            break;
        case 'move_services':
            $targetCat = validate_positive_int($_POST['target_category_id'] ?? null);
            if ($targetCat) {
                $params = array_merge([$targetCat], $ids);
                $pdo->prepare("UPDATE services SET category_id = ? WHERE category_id IN ($placeholders)")->execute($params);
                set_flash('success', 'Moved associated services into target category.');
            }
            break;
        case 'delete':
            $deleted = 0;
            $protected = 0;
            foreach ($ids as $id) {
                $res = Category::deleteProtected($id);
                if ($res['ok']) {
                    $deleted++;
                } else {
                    $protected++;
                }
            }
            set_flash($protected > 0 ? 'warning' : 'success', "Deleted {$deleted} empty category(s)." . ($protected > 0 ? " {$protected} protected because services are still assigned." : ''));
            break;
    }
    log_activity('admin', (int) $admin['id'], 'bulk_categories_' . $action, 'category', null, 'IDs: ' . implode(',', $ids));
    redirect('../admin/categories.php');
}

redirect('../admin/dashboard.php');
