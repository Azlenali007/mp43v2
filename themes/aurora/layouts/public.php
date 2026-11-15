<?php
declare(strict_types=1);
/** Aurora Public Layout Helper */
function aurora_public_nav_links(string $rp): array
{
    return [
        ['href' => $rp . 'index.php', 'label' => 'Home'],
        ['href' => $rp . 'services.php', 'label' => 'Catalog'],
        ['href' => $rp . 'faq.php', 'label' => 'FAQ'],
        ['href' => $rp . 'contact.php', 'label' => 'Contact'],
    ];
}
