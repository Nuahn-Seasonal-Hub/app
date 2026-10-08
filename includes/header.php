<?php
// includes/header.php — Nuahn Seasonal Hub app shell (opening half).
// Presentation only: renders <head>, the desktop top nav / mobile app bar and flash toasts.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/ui.php';

$nuPage   = nu_page_config();
$nuRole   = nu_role();
$nuUser   = $_SESSION['user_name'] ?? null;
$nuLogged = isset($_SESSION['user_id']);
$nuNav    = nu_nav_items();
$nuTheme  = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark' : 'light';
if ($nuRole === 'client' && $nuPage['tab'] === 'jobs') { $nuPage['tab'] = 'post'; }
$nuIsTab  = false;
foreach ($nuNav as $item) { if ($item[0] === $nuPage['tab']) { $nuIsTab = true; } }

$bodyClass = [
    'page-' . $nuPage['slug'],
    'role-' . ($nuRole ?: 'guest'),
    $nuPage['lean'] ? 'ui-lean' : 'ui-legacy',
    'has-tabbar',
    $nuLogged ? 'is-app' : 'is-guest',
    $nuIsTab ? 'is-tabpage' : '',
    ($nuPage['sub'] || !$nuIsTab) ? 'is-subpage' : '',
    $nuPage['landing'] ? 'page-landing' : '',
];
?><!DOCTYPE html>
<html lang="en" data-theme="<?= $nuTheme ?>" data-bs-theme="<?= $nuTheme ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= nu_e($nuPage['title']) ?> · Nuahn Seasonal Hub</title>
  <meta name="description" content="Nuahn Seasonal Hub connects clients who need short-term help with trusted seasonal providers. Post jobs, apply and hire in minutes.">
  <meta name="theme-color" content="#0A1A3F">
  <meta name="color-scheme" content="light dark">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Nuahn">
  <link rel="manifest" href="<?= nu_asset('public/assets/manifest.webmanifest') ?>">
  <link rel="icon" type="image/png" href="<?= nu_asset('public/assets/img/logo-72.png') ?>">
  <link rel="apple-touch-icon" href="<?= nu_asset('public/assets/img/icon-180.png') ?>">
  <link rel="preload" href="<?= nu_url('public/assets/fonts/jakarta-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <script>
    (function (d) {
      var e = d.documentElement; e.classList.add('js');
      try { var t = localStorage.getItem('nuahn-theme'); if (t === 'dark' || t === 'light') { e.setAttribute('data-theme', t); e.setAttribute('data-bs-theme', t); } } catch (_) {}
    })(document);
  </script>
<?php if (!$nuPage['lean']): ?>
  <link rel="stylesheet" href="<?= nu_asset('public/assets/vendor/bootstrap/bootstrap.min.css') ?>">
<?php endif; ?>
  <link rel="stylesheet" href="<?= nu_asset('public/assets/app.css') ?>">
<?php if ($nuPage['icons']): ?>
  <link rel="stylesheet" href="<?= nu_asset('public/assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<?php endif; ?>
<?php if ($nuPage['map'] && $nuPage['map'] !== 'lazy'): ?>
  <link rel="stylesheet" href="<?= nu_asset('public/assets/vendor/leaflet/leaflet.css') ?>">
<?php if ($nuPage['cluster']): ?>
  <link rel="stylesheet" href="<?= nu_asset('public/assets/vendor/leaflet/MarkerCluster.css') ?>">
  <link rel="stylesheet" href="<?= nu_asset('public/assets/vendor/leaflet/MarkerCluster.Default.css') ?>">
<?php endif; ?>
<?php $nuDefer = $nuPage['map'] === 'defer' ? ' defer' : ''; ?>
  <script src="<?= nu_asset('public/assets/vendor/leaflet/leaflet.js') ?>"<?= $nuDefer ?>></script>
<?php if ($nuPage['cluster']): ?>
  <script src="<?= nu_asset('public/assets/vendor/leaflet/leaflet.markercluster.js') ?>"<?= $nuDefer ?>></script>
<?php endif; ?>
  <script src="<?= nu_asset('assets/js/map.js') ?>"<?= $nuDefer ?>></script>
<?php endif; ?>
<?php if ($nuPage['chart']): ?>
  <script src="<?= nu_asset('public/assets/vendor/chart.umd.js') ?>" defer></script>
<?php endif; ?>
  <script src="<?= nu_asset('public/assets/app.js') ?>" defer></script>
  <script type="speculationrules">
  {"prefetch":[{"where":{"and":[{"href_matches":"<?= nu_base() ?>/*"},{"not":{"href_matches":"*logout*"}},{"not":{"href_matches":"<?= nu_base() ?>/actions/*"}},{"not":{"selector_matches":"[data-no-prefetch]"}}]},"eagerness":"moderate"}]}
  </script>
</head>
<body class="<?= nu_e(trim(implode(' ', array_filter($bodyClass)))) ?>"<?= $nuLogged ? ' data-theme-endpoint="' . nu_e(nu_url('actions/update_theme.php')) . '"' : '' ?><?php if ($nuPage['map'] === 'lazy'): ?> data-map-assets="<?= nu_e(json_encode(['css' => nu_asset('public/assets/vendor/leaflet/leaflet.css'), 'js' => nu_asset('public/assets/vendor/leaflet/leaflet.js'), 'helper' => nu_asset('assets/js/map.js')], JSON_UNESCAPED_SLASHES)) ?>"<?php endif; ?>>
<a class="skip-link" href="#main">Skip to content</a>

<header class="topnav" id="topnav">
  <div class="wrap topnav__inner">
    <a class="icon-btn appbar-back" href="<?= nu_e(nu_home_url()) ?>" data-back aria-label="Back"><?= nu_icon('chevron-left') ?></a>
    <a class="brand" href="<?= nu_e($nuLogged ? nu_home_url() : nu_url('public/index.php')) ?>" aria-label="Nuahn Seasonal Hub home">
      <img src="<?= nu_asset('public/assets/img/logo-72.webp') ?>" width="36" height="35" alt="">
      <span class="brand__name">Nuahn <span>Seasonal Hub</span></span>
    </a>
    <span class="appbar-title"><?= nu_e($nuPage['title']) ?></span>

    <nav class="navlinks" aria-label="Main">
<?php foreach ($nuNav as $item):
        if ($item[0] === 'profile' || (!$nuLogged && in_array($item[0], ['login', 'join'], true))) continue; ?>
      <a class="navlink" href="<?= nu_e($item[2]) ?>"<?= $item[0] === $nuPage['tab'] ? ' aria-current="page"' : '' ?>><?= nu_e($item[0] === 'post' ? 'Post a job' : $item[1]) ?></a>
<?php endforeach; ?>
<?php if ($nuRole === 'provider'): ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/discover_Jobs.php')) ?>"<?= $nuPage['rel'] === 'public/discover_Jobs.php' ? ' aria-current="page"' : '' ?>>Map</a>
<?php elseif (!$nuLogged): ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/index.php#how')) ?>">How it works</a>
<?php endif; ?>
    </nav>

    <div class="topnav__actions">
      <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle dark mode">
        <span class="theme-ic-light"><?= nu_icon('moon-stars') ?></span><span class="theme-ic-dark"><?= nu_icon('sun') ?></span>
      </button>
<?php if ($nuLogged): ?>
      <button type="button" class="avatar" popovertarget="user-menu" aria-label="Account menu"><?= nu_e(nu_initials($nuUser ?: 'U')) ?></button>
<?php else: ?>
      <a class="btn btn-glass btn-sm nav-cta-desktop" href="<?= nu_e(nu_url('public/login.php')) ?>">Sign in</a>
      <a class="btn btn-primary btn-sm nav-cta-desktop" href="<?= nu_e(nu_url('public/register.php')) ?>">Get started</a>
<?php endif; ?>
    </div>
  </div>
</header>

<?php
// Session flash messages (set via addFlash()/flashSuccess()/flashError()) shown as toasts.
$nuFlashes = [];
foreach (['flash_alert', 'flash_toast'] as $k) {
    if (!empty($_SESSION[$k]) && is_array($_SESSION[$k])) {
        foreach ($_SESSION[$k] as $type => $msgs) {
            foreach ((array) $msgs as $m) { $nuFlashes[] = [$type, $m]; }
        }
        unset($_SESSION[$k]);
    }
}
if ($nuFlashes): ?>
<div class="toasts" role="status" aria-live="polite">
<?php foreach ($nuFlashes as $f):
    $t = in_array($f[0], ['success', 'danger', 'warning', 'info'], true) ? $f[0] : 'info';
    $ic = ['success' => 'check-circle-fill', 'danger' => 'x-circle', 'warning' => 'bell', 'info' => 'bell'][$t]; ?>
  <div class="toast toast--<?= $t ?>"><?= nu_icon($ic) ?><span><?= nu_e($f[1]) ?></span></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="app-main" id="main">
