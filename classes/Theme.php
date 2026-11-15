<?php
declare(strict_types=1);

class Theme
{
    private static ?array $resolvedTheme = null;

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
            $manifest = $themesDir . '/' . $entry . '/theme.php';
            if (is_dir($themesDir . '/' . $entry) && is_file($manifest)) {
                $meta = require $manifest;
                if (is_array($meta) && !empty($meta['slug'])) {
                    $meta['slug'] = preg_replace('/[^a-z0-9_-]/i', '', (string) $meta['slug']);
                    $found[$meta['slug']] = $meta;
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
            // Ensure exactly one active theme
            $activeCount = (int) $pdo->query('SELECT COUNT(*) FROM themes WHERE is_active = 1 AND status = "active"')->fetchColumn();
            if ($activeCount === 0) {
                $pdo->exec('UPDATE themes SET is_active = 1 WHERE slug = "classic" LIMIT 1');
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * Resolves the global active theme from MySQL.
     * ONLY an authenticated administrator may pass ?preview_theme=<slug> to preview before activation.
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

            // Admin-only temporary theme preview guard
            if (!empty($_GET['preview_theme']) && function_exists('current_admin') && current_admin() !== null) {
                $reqSlug = preg_replace('/[^a-z0-9_-]/i', '', (string) $_GET['preview_theme']);
                $pStmt = $pdo->prepare('SELECT * FROM themes WHERE slug = ? AND status = "active" LIMIT 1');
                $pStmt->execute([$reqSlug]);
                $previewRow = $pStmt->fetch();
                if ($previewRow && is_dir(APP_ROOT . '/themes/' . $previewRow['slug'])) {
                    self::$resolvedTheme = $previewRow;
                    return self::$resolvedTheme;
                }
            }

            $stmt = $pdo->query('SELECT * FROM themes WHERE is_active = 1 AND status = "active" LIMIT 1');
            $row = $stmt->fetch();
            if ($row && is_dir(APP_ROOT . '/themes/' . $row['slug'])) {
                self::$resolvedTheme = $row;
                return self::$resolvedTheme;
            }
        } catch (Throwable $e) {
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
        $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
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
            log_activity('admin', $adminId, 'theme_activated', 'theme', (int) $theme['id'], "Activated theme: {$slug}");
            return ['ok' => true, 'message' => "Theme '{$theme['name']}' is now active globally."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function componentPath(string $relPath): ?string
    {
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/' . ltrim($relPath, '/');
        if (is_file($candidate)) {
            return $candidate;
        }
        $classicFallback = APP_ROOT . '/themes/classic/' . ltrim($relPath, '/');
        return is_file($classicFallback) ? $classicFallback : null;
    }

    public static function assetUrl(string $relPath): string
    {
        $prefix = app_root_prefix();
        $slug = self::activeSlug();
        $candidate = APP_ROOT . '/themes/' . $slug . '/assets/' . ltrim($relPath, '/');
        if (is_file($candidate)) {
            return $prefix . 'themes/' . $slug . '/assets/' . ltrim($relPath, '/');
        }
        return $prefix . 'themes/classic/assets/' . ltrim($relPath, '/');
    }
}
