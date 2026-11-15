<?php
declare(strict_types=1);
/** Classic Public Layout Helper */
function classic_public_nav_links(string $rp): array
{
    return [
        ['href' => $rp . 'index.php', 'label' => 'Home'],
        ['href' => $rp . 'services.php', 'label' => 'Services'],
        ['href' => $rp . 'faq.php', 'label' => 'FAQ'],
        ['href' => $rp . 'contact.php', 'label' => 'Contact'],
    ];
}
