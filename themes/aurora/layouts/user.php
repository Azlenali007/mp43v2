<?php
declare(strict_types=1);
/** Aurora User Layout Helper */
function aurora_user_nav_items(string $rp): array
{
    $items = [
        ['href' => $rp . 'dashboard.php', 'match' => 'dashboard.php', 'label' => 'Overview', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
        ['href' => $rp . 'new-order.php', 'match' => 'new-order.php', 'label' => 'New Order', 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
        ['href' => $rp . 'bulk-order.php', 'match' => 'bulk-order.php', 'label' => 'Bulk Orders', 'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ['href' => $rp . 'services.php', 'match' => 'services.php', 'label' => 'Service Catalog', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'],
        ['href' => $rp . 'orders.php', 'match' => 'orders.php', 'label' => 'Order History', 'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
        ['href' => $rp . 'add-funds.php', 'match' => 'add-funds.php', 'label' => 'Add Funds', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'],
        ['href' => $rp . 'transactions.php', 'match' => 'transactions.php', 'label' => 'Ledger', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
    ];

    if (class_exists('Tools')) {
        try {
            if (!empty(Tools::all(true))) {
                $items[] = [
                    'href'  => $rp . 'tools/index.php',
                    'match' => 'tools',
                    'label' => 'Creator Tools',
                    'icon'  => '<path d="M12 3l1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3z"/>',
                ];
            }
        } catch (Throwable $e) {
        }
    }

    $items[] = ['href' => $rp . 'tickets.php', 'match' => 'tickets.php', 'label' => 'Support', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'];
    $items[] = ['href' => $rp . 'api-access.php', 'match' => 'api-access.php', 'label' => 'API', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'];
    $items[] = ['href' => $rp . 'profile.php', 'match' => 'profile.php', 'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'];
    $items[] = ['href' => $rp . 'faq.php', 'match' => 'faq.php', 'label' => 'FAQ', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'];
    $items[] = ['href' => $rp . 'contact.php', 'match' => 'contact.php', 'label' => 'Contact', 'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'];
    return $items;
}
