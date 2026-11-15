<?php
declare(strict_types=1);
require_once __DIR__ . '/../layouts/public.php';
$siteName = get_setting('site_name', 'CrownSMM');
$loggedUser = current_user();
$rp = app_root_prefix();
$navLinks = aurora_public_nav_links($rp);
$isAdminPreview = !empty($_GET['preview_theme']) && function_exists('current_admin') && current_admin() !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle ?? $siteName) ?> — <?= e($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="<?= e($rp) ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= e(Theme::assetUrl('css/theme.css')) ?>">
</head>
<body class="theme-aurora bg-[#0B1220] text-[#F1F5F9] min-h-screen flex flex-col">
  <?php if ($isAdminPreview): ?>
    <div class="bg-[#0D9488] text-white text-xs font-semibold px-4 py-2 text-center">
      Admin Theme Preview Mode: <strong><?= e(Theme::activeSlug()) ?></strong> — <a href="<?= e($rp) ?>admin/themes.php" class="underline ml-2">Return to Theme Manager</a>
    </div>
  <?php endif; ?>
  <header class="border-b border-slate-800 bg-[#0D1628]/95 backdrop-blur sticky top-0 z-40">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-8">
      <a href="<?= e($rp) ?>index.php" class="flex items-center gap-2.5 whitespace-nowrap shrink-0">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#0D9488] to-[#4F46E5] flex items-center justify-center text-white shadow-sm">
          <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
        </span>
        <span class="text-lg font-bold tracking-tight text-white"><?= e($siteName) ?></span>
      </a>

      <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-300">
        <?php foreach ($navLinks as $link): ?>
          <a href="<?= e($link['href']) ?>" class="hover:text-[#2DD4BF] transition whitespace-nowrap shrink-0"><?= e($link['label']) ?></a>
        <?php endforeach; ?>
      </nav>

      <div class="flex items-center gap-3 shrink-0">
        <?php if ($loggedUser): ?>
          <a href="<?= e($rp) ?>dashboard.php" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#4F46E5] text-white text-sm font-semibold hover:opacity-95 transition whitespace-nowrap shrink-0">
            Dashboard
          </a>
        <?php else: ?>
          <a href="<?= e($rp) ?>login.php" class="px-4 py-2 text-sm font-semibold text-slate-200 hover:text-[#2DD4BF] transition whitespace-nowrap shrink-0">Sign In</a>
          <a href="<?= e($rp) ?>register.php" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#4F46E5] text-white text-sm font-semibold hover:opacity-95 transition whitespace-nowrap shrink-0">
            Get Started
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <main class="flex-1">
