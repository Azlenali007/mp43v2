<?php
declare(strict_types=1);
require_once __DIR__ . '/ai-client.php';

class ToolFunctions
{
    public static function generate(string $slug, array $input): array
    {
        return match ($slug) {
            'instagram-caption' => self::generateInstagramCaption($input),
            'instagram-bio'     => self::generateInstagramBio($input),
            'youtube-seo'       => self::generateYoutubeSeo($input),
            default             => ['ok' => false, 'code' => 'UNKNOWN_TOOL', 'message' => 'Unsupported creator tool.'],
        };
    }

    private static function generateInstagramCaption(array $input): array
    {
        $topic = trim((string) ($input['topic'] ?? ''));
        $tone = trim((string) ($input['tone'] ?? 'Engaging'));
        $niche = trim((string) ($input['niche'] ?? 'Creator'));
        $language = trim((string) ($input['language'] ?? 'English'));

        if ($topic === '') {
            return ['ok' => false, 'code' => 'VALIDATION', 'message' => 'Please describe your Instagram post topic or reel idea.'];
        }

        $system = 'You are an expert Instagram growth copywriter. Return strict JSON only.';
        $prompt = <<<PROMPT
Generate Instagram captions and hashtag bundles for:
- Post Topic: {$topic}
- Niche / Industry: {$niche}
- Tone: {$tone}
- Language: {$language}

Return strictly valid JSON with keys:
{
  "captions": [
    {"style": "Short & Punchy", "text": "...", "cta": "..."},
    {"style": "Storytelling", "text": "...", "cta": "..."},
    {"style": "Hook + Value", "text": "...", "cta": "..."}
  ],
  "hashtags": ["#tag1", "#tag2", "...12 to 18 relevant non-spam hashtags..."],
  "posting_tip": "One crisp tip for maximizing engagement on this post."
}
PROMPT;

        $res = AIClient::generateStructured($system, $prompt);
        if (!$res['ok']) {
            return $res;
        }

        $data = $res['data'];
        $captions = [];
        foreach (($data['captions'] ?? []) as $c) {
            if (!is_array($c) || empty($c['text'])) {
                continue;
            }
            $captions[] = [
                'style' => (string) ($c['style'] ?? 'Caption'),
                'text'  => trim((string) $c['text']),
                'cta'   => trim((string) ($c['cta'] ?? '')),
            ];
        }
        $hashtags = [];
        foreach (($data['hashtags'] ?? []) as $h) {
            $tag = trim((string) $h);
            if ($tag !== '') {
                if ($tag[0] !== '#') {
                    $tag = '#' . ltrim($tag, '#');
                }
                $hashtags[] = preg_replace('/\s+/', '', $tag);
            }
        }

        if (empty($captions)) {
            return ['ok' => false, 'code' => 'EMPTY_RESULT', 'message' => 'AI did not return valid caption options. Please try regenerating.'];
        }

        return [
            'ok'         => true,
            'provider'   => $res['provider'],
            'model'      => $res['model'],
            'latency_ms' => $res['latency_ms'],
            'result'     => [
                'captions'    => $captions,
                'hashtags'    => array_values(array_unique($hashtags)),
                'posting_tip' => (string) ($data['posting_tip'] ?? ''),
            ],
        ];
    }

    private static function generateInstagramBio(array $input): array
    {
        $brand = trim((string) ($input['brand'] ?? ''));
        $niche = trim((string) ($input['niche'] ?? ''));
        $audience = trim((string) ($input['audience'] ?? 'Creators & followers'));
        $cta = trim((string) ($input['cta'] ?? 'DM for collabs'));
        $tone = trim((string) ($input['tone'] ?? 'Clean & Authority'));

        if ($brand === '' && $niche === '') {
            return ['ok' => false, 'code' => 'VALIDATION', 'message' => 'Please enter your name/brand or niche to generate bios.'];
        }

        $system = 'You are an Instagram profile optimization specialist. Every single bio you produce MUST be 150 characters or fewer (strictly <= 150 characters including spaces, line breaks, and emojis). Return strict JSON only.';
        $prompt = <<<PROMPT
Create 4 distinct Instagram bios for:
- Brand / Name: {$brand}
- Niche / Value Proposition: {$niche}
- Target Audience: {$audience}
- Call To Action: {$cta}
- Tone: {$tone}

CRITICAL RULE: Each bio string MUST be <= 150 characters total!
Return strictly valid JSON:
{
  "bios": [
    {"label": "Authority Builder", "bio": "... <=150 chars ..."},
    {"label": "Minimalist", "bio": "... <=150 chars ..."},
    {"label": "Creator / Fun", "bio": "... <=150 chars ..."},
    {"label": "Conversion Focused", "bio": "... <=150 chars ..."}
  ]
}
PROMPT;

        $res = AIClient::generateStructured($system, $prompt);
        if (!$res['ok']) {
            return $res;
        }

        $bios = [];
        foreach (($res['data']['bios'] ?? []) as $item) {
            if (!is_array($item) || empty($item['bio'])) {
                continue;
            }
            $text = trim((string) $item['bio']);
            // Enforce Instagram's hard 150-character ceiling server-side
            if (mb_strlen($text, 'UTF-8') > 150) {
                $text = rtrim(mb_substr($text, 0, 149, 'UTF-8')) . '…';
            }
            $bios[] = [
                'label'      => (string) ($item['label'] ?? 'Option'),
                'bio'        => $text,
                'char_count' => mb_strlen($text, 'UTF-8'),
            ];
        }

        if (empty($bios)) {
            return ['ok' => false, 'code' => 'EMPTY_RESULT', 'message' => 'AI did not return valid bio options.'];
        }

        return [
            'ok'         => true,
            'provider'   => $res['provider'],
            'model'      => $res['model'],
            'latency_ms' => $res['latency_ms'],
            'result'     => ['bios' => $bios],
        ];
    }

    private static function generateYoutubeSeo(array $input): array
    {
        $topic = trim((string) ($input['topic'] ?? ''));
        $keywords = trim((string) ($input['keywords'] ?? ''));
        $audience = trim((string) ($input['audience'] ?? 'General viewers'));
        $videoType = trim((string) ($input['video_type'] ?? 'Long-form YouTube Video'));

        if ($topic === '') {
            return ['ok' => false, 'code' => 'VALIDATION', 'message' => 'Please enter your video topic or working title.'];
        }

        $system = 'You are a YouTube SEO and CTR optimization strategist. Return strict JSON only.';
        $prompt = <<<PROMPT
Generate a full YouTube SEO optimization package for:
- Video Topic / Idea: {$topic}
- Primary Keywords: {$keywords}
- Target Audience: {$audience}
- Format: {$videoType}

Return strictly valid JSON:
{
  "titles": ["High-CTR Title 1", "High-CTR Title 2", "Search-Optimized Title 3", "Curiosity Title 4"],
  "description": "Well-structured 2-3 paragraph SEO-rich video description with natural keywords and CTA.",
  "tags": ["tag 1", "tag 2", "...12 to 16 keyword tags..."],
  "hashtags": ["#Tag1", "#Tag2", "#Tag3"]
}
PROMPT;

        $res = AIClient::generateStructured($system, $prompt);
        if (!$res['ok']) {
            return $res;
        }

        $data = $res['data'];
        $titles = array_values(array_filter(array_map(fn($t) => trim((string) $t), $data['titles'] ?? [])));
        $description = trim((string) ($data['description'] ?? ''));
        $tags = array_values(array_filter(array_map(fn($t) => trim((string) $t), $data['tags'] ?? [])));
        $hashtags = array_values(array_filter(array_map(fn($t) => trim((string) $t), $data['hashtags'] ?? [])));

        if (empty($titles) || $description === '') {
            return ['ok' => false, 'code' => 'EMPTY_RESULT', 'message' => 'AI did not return a complete YouTube SEO package.'];
        }

        return [
            'ok'         => true,
            'provider'   => $res['provider'],
            'model'      => $res['model'],
            'latency_ms' => $res['latency_ms'],
            'result'     => [
                'titles'      => $titles,
                'description' => $description,
                'tags'        => $tags,
                'hashtags'    => $hashtags,
            ],
        ];
    }
}
