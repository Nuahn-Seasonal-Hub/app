<?php
// Launch gate: public domain shows only the Coming Soon page until launch (includes/gate.php).
require_once __DIR__ . '/gate.php';
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
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
$nuUnread = nu_unread_count();
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
    $nuPage['console'] ? 'is-console' : '',
];
?><!DOCTYPE html>
<html lang="en" data-theme="<?= $nuTheme ?>" data-bs-theme="<?= $nuTheme ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= nu_e($nuPage['title']) ?> · Nuahn Seasonal Hub</title>
  <meta name="description" content="Every season. One trusted hub. Nuahn is a marketplace connecting homeowners with vetted local independent pros for Canadian seasonal care. Winter snow removal and mobile mechanics launch first, end of November 2026, with more services coming soon.">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="color-scheme" content="light dark">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Nuahn">
  <link rel="manifest" href="<?= nu_asset('public/assets/manifest.webmanifest') ?>">
  <link rel="icon" href="<?= nu_asset('public/assets/img/favicon.ico') ?>" sizes="any">
  <link rel="icon" type="image/png" sizes="48x48" href="<?= nu_asset('public/assets/img/favicon-48.png') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= nu_asset('public/assets/img/favicon-32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= nu_asset('public/assets/img/favicon-16.png') ?>">
  <link rel="apple-touch-icon" href="<?= nu_asset('public/assets/img/icon-180.png') ?>">
  <link rel="preload" href="<?= nu_url('public/assets/fonts/jakarta-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= nu_url('public/assets/fonts/dmserif-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
<?php if (!empty($GLOBALS['nuPreload'])): echo '  ' . $GLOBALS['nuPreload'] . "\n"; endif; ?>
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
    <a class="brand" href="<?= nu_e($nuLogged ? nu_home_url() : nu_url('public/index.php')) ?>"><?= nu_logo(36) ?></a>
    <span class="appbar-title"><?= nu_e($nuPage['title']) ?></span>

    <nav class="navlinks" aria-label="Main">
<?php if ($nuRole === 'client' || !$nuLogged): ?>
      <button type="button" class="navlink navlink--menu" popovertarget="services-menu"<?= $nuPage['tab'] === 'book' ? ' aria-current="page"' : '' ?>>Services <?= nu_icon('chevron-down') ?></button>
      <a class="navlink" href="<?= nu_e(nu_url('public/bookings.php')) ?>"<?= $nuPage['tab'] === 'bookings' ? ' aria-current="page"' : '' ?>>My Bookings</a>
<?php if ($nuLogged): ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/activity.php')) ?>"<?= $nuPage['tab'] === 'activity' ? ' aria-current="page"' : '' ?>>Activity</a>
<?php else: ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/plans.php')) ?>"<?= $nuPage['rel'] === 'public/plans.php' ? ' aria-current="page"' : '' ?>>Plans</a>
<?php endif; ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/updates.php')) ?>"<?= $nuPage['tab'] === 'messages' ? ' aria-current="page"' : '' ?>>Updates<?= $nuUnread ? '<span class="dot" aria-label="' . (int) $nuUnread . ' unread"></span>' : '' ?></a>
<?php else: foreach ($nuNav as $item):
        if ($item[0] === 'profile') continue; ?>
      <a class="navlink" href="<?= nu_e($item[2]) ?>"<?= $item[0] === $nuPage['tab'] ? ' aria-current="page"' : '' ?>><?= nu_e($item[1]) ?><?= ($item[0] === 'messages' && $nuUnread) ? '<span class="dot" aria-label="' . (int) $nuUnread . ' unread"></span>' : '' ?></a>
<?php endforeach; ?>
<?php if ($nuRole === 'provider'): ?>
      <a class="navlink" href="<?= nu_e(nu_url('public/discover_Jobs.php')) ?>"<?= $nuPage['rel'] === 'public/discover_Jobs.php' ? ' aria-current="page"' : '' ?>>Map</a>
<?php endif; endif; ?>
    </nav>

    <div class="topnav__actions">
      <button type="button" class="icon-btn" data-theme-toggle aria-label="Toggle dark mode">
        <span class="theme-ic-light"><?= nu_icon('moon-stars') ?></span><span class="theme-ic-dark"><?= nu_icon('sun') ?></span>
      </button>
<?php if ($nuLogged): ?>
      <button type="button" class="account-btn" popovertarget="user-menu"><span class="avatar"><?= nu_e(nu_initials($nuUser ?: 'U')) ?></span><span class="account-btn__label">Account</span></button>
<?php else: ?>
      <a class="btn btn-outline btn-sm nav-cta-desktop" href="<?= nu_e(nu_url('public/login.php')) ?>">Account · Sign in</a>
      <a class="btn btn-primary btn-sm nav-cta-desktop" href="<?= nu_e(nu_url('public/register.php')) ?>">Get started</a>
<?php endif; ?>
    </div>
  </div>
</header>
<?php if ($nuRole === 'client' || !$nuLogged): ?>
<div id="services-menu" class="sheet sheet--services" popover>
  <div class="sheet__label">Launching first, end of November</div>
<?php foreach (nu_services_by_status('live') as $nuSvSlug => $nuSv): ?>
  <a class="menu-item" href="<?= nu_e(nu_book_url($nuSvSlug)) ?>"><?= nu_icon($nuSv['icon']) ?> <span><?= nu_e($nuSv['name']) ?><small><?= nu_e($nuSv['blurb']) ?></small></span></a>
<?php endforeach; ?>
  <div class="sheet__label">Coming soon</div>
<?php foreach (nu_services_by_status('coming_soon') as $nuSvSlug => $nuSv): ?>
  <a class="menu-item is-soon" href="<?= nu_e(nu_book_url($nuSvSlug)) ?>"><?= nu_icon($nuSv['icon']) ?> <span><?= nu_e($nuSv['name']) ?><small>Not bookable yet. Get notified when it launches.</small></span><?= nu_soon_chip() ?></a>
<?php endforeach; ?>
  <div class="menu-sep"></div>
  <a class="menu-item" href="<?= nu_e(nu_url('public/mechanics.php')) ?>"><?= nu_icon('tools') ?> <span>Roadside help &amp; repairs<small>Mobile mechanic service menu</small></span></a>
  <a class="menu-item" href="<?= nu_e(nu_url('public/plans.php')) ?>"><?= nu_icon('arrow-repeat') ?> <span>Basic or Premium<small>Book when needed, or get monthly provider reminders</small></span></a>
  <a class="menu-item" href="<?= nu_e($nuLogged ? nu_url('public/jobs.php#post') : nu_url('public/register_client.php')) ?>"><?= nu_icon('plus-circle-fill') ?> <span>Other jobs<small>Post any job and get offers from local providers</small></span></a>
</div>
<?php endif; ?>

<?php
// Session flash messages (set via addFlash()/flashSuccess()/flashError()) shown as toasts.
$nuFlashes = [];
foreach (['flash_alert', 'flash_toast'] as $nuK) {
    if (!empty($_SESSION[$nuK]) && is_array($_SESSION[$nuK])) {
        foreach ($_SESSION[$nuK] as $nuTy => $nuMs) {
            foreach ((array) $nuMs as $nuM) { $nuFlashes[] = [$nuTy, $nuM]; }
        }
        unset($_SESSION[$nuK]);
    }
}
if ($nuFlashes): ?>
<div class="toasts" role="status" aria-live="polite">
<?php foreach ($nuFlashes as $nuF):
    $nuFt = in_array($nuF[0], ['success', 'danger', 'warning', 'info'], true) ? $nuF[0] : 'info';
    $nuFi = ['success' => 'check-circle-fill', 'danger' => 'x-circle', 'warning' => 'bell', 'info' => 'bell'][$nuFt]; ?>
  <div class="toast toast--<?= $nuFt ?>"><?= nu_icon($nuFi) ?><span><?= nu_e($nuF[1]) ?></span></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="app-main" id="main">
