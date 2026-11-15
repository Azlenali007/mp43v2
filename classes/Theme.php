<?php
declare(strict_types=1);

class Theme
{
    private static ?array $resolvedTheme = null;
    private static ?array $sharedCtxCache = null;

    public static function safeSlug(?string $slug): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', (string) $slug) ?: '';
    }

    public static function safeRelPath(string $relPath): string
    {
        $clean = str_replace('\\', '/', trim($relPath));
        $clean = ltrim($clean, '/');
        $clean = str_replace("\0", '', $clean);
        if (str_contains($clean, '..')) {
            return '';
        }
        return $clean;
    }

    public static function discoverFilesystemThemes(): array
    {
        $themesDir = APP_ROOT . '/themes';
        if (!is_dir($themesDir)) {
            return [];
        }
        $found = [];
        foreach (scandir($themesDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $slugSafe = self::safeSlug($entry);
            if ($slugSafe === '' || $slugSafe !== $entry) {
                continue;
            }
            $manifest = $themesDir . '/' . $slugSafe . '/theme.php';
            if (is_dir($themesDir . '/' . $slugSafe) && is_file($manifest)) {
                $meta = require $manifest;
                if (is_array($meta) && !empty($meta['slug'])) {
                    $s = self::safeSlug((string) $meta['slug']);
                    if ($s !== '') {
                        $meta['slug'] = $s;
                        $found[$s] = $meta;
                    }
                }
            }
        }
        return $found;
    }

    public static function all(): array
    {
        self::syncWithFilesystem();
        $pdo = Database::getConnection();
        return $pdo->query('SELECT * FROM themes ORDER BY is_active DESC, id ASC')->fetchAll();
    }

    public static function syncWithFilesystem(): void
    {
        try {
            $pdo = Database::getConnection();
            $fsThemes = self::discoverFilesystemThemes();
            foreach ($fsThemes as $slug => $meta) {
                $stmt = $pdo->prepare(
                    'INSERT INTO themes (slug, name, description, version, author, preview_accent, is_active, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "active")
                     ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), version = VALUES(version), author = VALUES(author), preview_accent = VALUES(preview_accent)'
                );
                $stmt->execute([
                    $slug,
                    (string) ($meta['name'] ?? ucfirst($slug)),
                    (string) ($meta['description'] ?? ''),
                    (string) ($meta['version'] ?? '1.0.0'),
                    (string) ($meta['author'] ?? 'System'),
                    (string) ($meta['preview_accent'] ?? '#7138D8'),
                    $slug === 'classic' ? 1 : 0,
                ]);
            }
            $activeCount = (int) $pdo->query('SELECT COUNT(*) FROM themes WHERE is_active = 1 AND status = "active"')->fetchColumn();
            if ($activeCount === 0) {
                $pdo->exec('UPDATE themes SET is_active = 1 WHERE slug = "classic" LIMIT 1');
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * Resolves the active theme from MySQL with strict directory validation & classic fallback.
     * ONLY authenticated administrators may preview via ?preview_theme=<slug>.
     */
    public static function current(): array
    {
        if (self::$resolvedTheme !== null) {
            return self::$resolvedTheme;
        }

        $fallback = [
            'slug'           => 'classic',
            'name'           => 'Classic Studio',
            'preview_accent' => '#7138D8',
        ];

        try {
            $pdo = Database::getConnection();

            if (!empty($_GET['preview_theme']) && function_exists('current_admin') && current_admin() !== null) {
                $reqSlug = self::safeSlug((string) $_GET['preview_theme']);
                if ($reqSlug !== '') {
                    $pStmt = $pdo->prepare('SELECT * FROM themes WHERE slug = ? AND status = "active" LIMIT 1');
                    $pStmt->execute([$reqSlug]);
                    $previewRow = $pStmt->fetch();
                    if ($previewRow && is_dir(APP_ROOT . '/themes/' . $previewRow['slug']) && is_file(APP_ROOT . '/themes/' . $previewRow['slug'] . '/theme.php')) {
                        self::$resolvedTheme = $previewRow;
                        return self::$resolvedTheme;
                    }
                }
            }

            $stmt = $pdo->query('SELECT * FROM themes WHERE is_active = 1 AND status = "active" LIMIT 1');
            $row = $stmt->fetch();
            if ($row) {
                $slug = self::safeSlug((string) ($row['slug'] ?? ''));
                if ($slug !== '' && is_dir(APP_ROOT . '/themes/' . $slug) && is_file(APP_ROOT . '/themes/' . $slug . '/theme.php')) {
                    $row['slug'] = $slug;
                    self::$resolvedTheme = $row;
                    return self::$resolvedTheme;
                }
            }
        } catch (Throwable $e) {
            if (function_exists('write_app_log')) {
                write_app_log('theme', 'Theme::current fallback to classic: ' . $e->getMessage());
            }
        }

        self::$resolvedTheme = $fallback;
        return self::$resolvedTheme;
    }

    public static function activeSlug(): string
    {
        return (string) (self::current()['slug'] ?? 'classic');
    }

    public static function activate(string $slug, int $adminId): array
    {
        $slug = self::safeSlug($slug);
        $fs = self::discoverFilesystemThemes();
        if (!isset($fs[$slug])) {
            return ['ok' => false, 'error' => 'Selected theme files were not found in /themes/.'];
        }

        self::syncWithFilesystem();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM themes WHERE slug = ? AND status = "active" LIMIT 1');
        $stmt->execute([$slug]);
        $theme = $stmt->fetch();
        if (!$theme) {
            return ['ok' => false, 'error' => 'Theme not found or is disabled.'];
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE themes SET is_active = 0');
            $pdo->prepare('UPDATE themes SET is_active = 1 WHERE slug = ?')->execute([$slug]);
            set_setting('active_theme', $slug, 'themes');
            $pdo->commit();
            self::$resolvedTheme = null;
            self::$sharedCtxCache = null;
            log_activity('admin', $adminId, 'theme_activated', 'theme', (int) $theme['id'], "Activated theme: {$slug}");
            return ['ok' => true, 'message' => "Theme '{$theme['name']}' is now active globally."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function pagePath(string $pageFile): string
    {
        $clean = self::safeRelPath($pageFile);
        if ($clean === '' || !str_ends_with($clean, '.php')) {
            return APP_ROOT . '/themes/classic/index.php';
        }
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/' . $clean;
        if (is_file($candidate)) {
            return $candidate;
        }
        $classicFallback = APP_ROOT . '/themes/classic/' . $clean;
        if (is_file($classicFallback)) {
            return $classicFallback;
        }
        throw new RuntimeException("Theme page template not found: {$clean}");
    }

    public static function layoutPath(string $layoutName): string
    {
        $safeName = self::safeSlug($layoutName);
        if ($safeName === '') {
            $safeName = 'main';
        }
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/layouts/' . $safeName . '.php';
        if (is_file($candidate)) {
            return $candidate;
        }
        return APP_ROOT . '/themes/classic/layouts/' . $safeName . '.php';
    }

    public static function componentPath(string $relPath): ?string
    {
        $clean = self::safeRelPath($relPath);
        if ($clean === '') {
            return null;
        }
        if (!str_contains($clean, '/')) {
            $clean = 'components/' . $clean;
        }
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/' . $clean;
        if (is_file($candidate)) {
            return $candidate;
        }
        $classicFallback = APP_ROOT . '/themes/classic/' . $clean;
        return is_file($classicFallback) ? $classicFallback : null;
    }

    public static function assetUrl(string $relPath): string
    {
        $clean = self::safeRelPath($relPath);
        $prefix = app_root_prefix();
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/assets/' . $clean;
        if ($clean !== '' && is_file($candidate)) {
            return $prefix . 'themes/' . $slug . '/assets/' . $clean . '?v=' . (@filemtime($candidate) ?: '1');
        }
        $fallback = APP_ROOT . '/themes/classic/assets/' . $clean;
        $ver = is_file($fallback) ? (@filemtime($fallback) ?: '1') : '1';
        return $prefix . 'themes/classic/assets/' . $clean . '?v=' . $ver;
    }

    /**
     * Build common shared view variables used by headers, sidebars, currency dropdowns, and layouts.
     */
    public static function sharedViewContext(?array $userOverride = null, bool $consumeFlash = true): array
    {
        if (self::$sharedCtxCache === null) {
            $user = $userOverride ?? (function_exists('current_user') ? current_user() : null);
            $siteName = get_setting('site_name', 'CrownSMM');
            $rp = app_root_prefix();
            $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
            $activeTheme = self::current();
            $activeThemeSlug = (string) ($activeTheme['slug'] ?? 'classic');
            $isAdminPreview = !empty($_GET['preview_theme']) && function_exists('current_admin') && current_admin() !== null;

            $unreadCount = 0;
            $notifications = [];
            if (!empty($user)) {
                try {
                    $pdo = Database::getConnection();
                    $nStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
                    $nStmt->execute([(int) $user['id']]);
                    $notifications = $nStmt->fetchAll();
                    foreach ($notifications as $n) {
                        if ((int) $n['is_read'] === 0) {
                            $unreadCount++;
                        }
                    }
                } catch (Throwable $e) {
                }
            }

            $initials = 'U';
            if (!empty($user['name'])) {
                $parts = preg_split('/\s+/', trim((string) $user['name']));
                $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1)) ?: 'U';
            }

            $displayCurrency = Currency::getUserDisplay($user);
            $enabledCurrencies = Currency::all(true, false);
            $readyCurrencies = Currency::all(true, true);

            self::$sharedCtxCache = [
                'siteName'          => $siteName,
                'rootPrefix'        => $rp,
                'rp'                => $rp,
                'currentScript'     => $currentScript,
                'activeTheme'       => $activeTheme,
                'activeThemeSlug'   => $activeThemeSlug,
                'isAdminPreview'    => $isAdminPreview,
                'unreadCount'       => $unreadCount,
                'notifications'     => $notifications,
                'initials'          => $initials,
                'displayCurrency'   => $displayCurrency,
                'enabledCurrencies' => $enabledCurrencies,
                'readyCurrencies'   => $readyCurrencies,
                'loggedUser'        => $user,
                'user'              => $user,
                'csrfToken'         => csrf_token(),
            ];
        }

        $ctx = self::$sharedCtxCache;
        if ($userOverride !== null) {
            $ctx['user'] = $userOverride;
            $ctx['loggedUser'] = $userOverride;
            $ctx['displayCurrency'] = Currency::getUserDisplay($userOverride);
        }
        $ctx['flash'] = $consumeFlash ? get_flash() : null;
        return $ctx;
    }

    public static function renderPage(string $pageFile, array $data = []): void
    {
        if (!defined('THEME_RENDER_CTX')) {
            define('THEME_RENDER_CTX', true);
        }
        $userOverride = array_key_exists('user', $data) && is_array($data['user']) ? $data['user'] : null;
        $ctx = array_merge(self::sharedViewContext($userOverride, true), $data);
        $path = self::pagePath($pageFile);
        extract($ctx, EXTR_SKIP);
        require $path;
    }

    public static function renderLayoutPart(string $layoutName, string $layoutMode, array $data = []): void
    {
        if (!defined('THEME_RENDER_CTX')) {
            define('THEME_RENDER_CTX', true);
        }
        $userOverride = array_key_exists('user', $data) && is_array($data['user']) ? $data['user'] : null;
        $consumeFlash = ($layoutMode === 'header');
        $ctx = array_merge(self::sharedViewContext($userOverride, $consumeFlash), $data);
        $ctx['layoutMode'] = $layoutMode;
        $path = self::layoutPath($layoutName);
        extract($ctx, EXTR_SKIP);
        require $path;
    }
}
