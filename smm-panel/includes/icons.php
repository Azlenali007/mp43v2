<?php
declare(strict_types=1);

/**
 * Configurable Platform & Service SVG Icon Mapping System
 * Icons are mapped deterministically by icon_key or platform slug.
 */
function icon_registry(): array
{
    return [
        'instagram' => [
            'label' => 'Instagram',
            'bg'    => 'bg-gradient-to-tr from-[#F58529] via-[#DD2A7B] to-[#8134AF]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'bg'    => 'bg-[#FF0000]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'bg'    => 'bg-[#1877F2]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        ],
        'telegram' => [
            'label' => 'Telegram',
            'bg'    => 'bg-[#229ED9]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'bg'    => 'bg-[#151515]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.87a8.28 8.28 0 0 0 4.76 1.5V6.93a4.84 4.84 0 0 1-1-.24z"/></svg>',
        ],
        'twitter' => [
            'label' => 'X (Twitter)',
            'bg'    => 'bg-[#151515]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'bg'    => 'bg-[#0A66C2]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        ],
        'spotify' => [
            'label' => 'Spotify',
            'bg'    => 'bg-[#1DB954]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>',
        ],
        'twitch' => [
            'label' => 'Twitch',
            'bg'    => 'bg-[#9146FF]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/></svg>',
        ],
        'discord' => [
            'label' => 'Discord',
            'bg'    => 'bg-[#5865F2]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028z"/></svg>',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'bg'    => 'bg-[#25D366]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        ],
        'threads' => [
            'label' => 'Threads',
            'bg'    => 'bg-[#151515]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M19 12a7 7 0 1 0-2.5 5.36"/><path d="M15.5 12c0 2-1 3.5-3.5 3.5S8.5 14 8.5 12 9.5 8.5 12 8.5s3.5 1.5 3.5 3.5v1a2 2 0 0 0 4 0v-1"/></svg>',
        ],
        'pinterest' => [
            'label' => 'Pinterest',
            'bg'    => 'bg-[#E60023]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><circle cx="12" cy="12" r="10"/><path d="M8 20l4-9"/><path d="M10.7 14c.4 1.1 1.6 1.5 2.7 1.2 1.8-.5 2.7-2.4 2.3-4.3-.4-2-2.2-3.2-4.2-2.9-2.2.3-3.6 2.3-3.1 4.5.2.8.7 1.4 1.3 1.8"/></svg>',
        ],
        'followers' => [
            'label' => 'Followers / Members',
            'bg'    => 'bg-[#7138D8]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
        ],
        'likes' => [
            'label' => 'Likes / Reactions',
            'bg'    => 'bg-[#E11D48]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        ],
        'views' => [
            'label' => 'Views / Impressions',
            'bg'    => 'bg-[#0EA5E9]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        ],
        'comments' => [
            'label' => 'Comments',
            'bg'    => 'bg-[#F59E0B]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        ],
        'shares' => [
            'label' => 'Shares / Retweets',
            'bg'    => 'bg-[#16A36A]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
        ],
        'bolt' => [
            'label' => 'Fast / Boost',
            'bg'    => 'bg-[#7138D8]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        ],
        'shield' => [
            'label' => 'Refill / Guaranteed',
            'bg'    => 'bg-[#16A36A]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
        ],
        'globe' => [
            'label' => 'General / Web',
            'bg'    => 'bg-[#151515]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        ],
        'sparkles' => [
            'label' => 'Premium Service',
            'bg'    => 'bg-[#7138D8]',
            'text'  => 'text-white',
            'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M12 3l1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3z"/></svg>',
        ],
    ];
}

function detect_icon_key_from_text(string $text): string
{
    $lower = strtolower($text);
    $platformKeywords = [
        'instagram' => ['instagram', 'ig ', 'insta'],
        'youtube'   => ['youtube', 'yt ', 'shorts'],
        'facebook'  => ['facebook', 'fb '],
        'telegram'  => ['telegram', 'tg '],
        'tiktok'    => ['tiktok', 'tik tok'],
        'twitter'   => ['twitter', 'x.com', 'tweet'],
        'linkedin'  => ['linkedin'],
        'spotify'   => ['spotify'],
        'twitch'    => ['twitch'],
        'discord'   => ['discord'],
        'whatsapp'  => ['whatsapp'],
        'threads'   => ['threads'],
        'pinterest' => ['pinterest'],
    ];
    foreach ($platformKeywords as $key => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return $key;
            }
        }
    }
    $serviceKeywords = [
        'followers' => ['follower', 'subscriber', 'member'],
        'likes'     => ['like', 'heart', 'reaction'],
        'views'     => ['view', 'watch', 'impression', 'play'],
        'comments'  => ['comment', 'reply'],
        'shares'    => ['share', 'repost', 'retweet'],
    ];
    foreach ($serviceKeywords as $key => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return $key;
            }
        }
    }
    return 'globe';
}

function render_icon_badge(string $iconKey, string $sizeClass = 'w-9 h-9', string $paddingClass = 'p-2'): string
{
    $registry = icon_registry();
    $item = $registry[$iconKey] ?? $registry['globe'];
    return sprintf(
        '<span class="inline-flex items-center justify-center rounded-xl shrink-0 %s %s %s %s shadow-sm">%s</span>',
        e($sizeClass),
        e($paddingClass),
        $item['bg'],
        $item['text'],
        $item['svg']
    );
}
