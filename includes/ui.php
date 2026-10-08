<?php
// includes/ui.php — view helpers for the Nuahn UI shell (presentation only).
// Safe to include more than once.

if (!function_exists('nu_base')) {

/** URL prefix of the app root relative to the web document root ('' on Fly). */
function nu_base() {
    static $base = null;
    if ($base !== null) return $base;
    $app = realpath(__DIR__ . '/..');
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $base = '';
    if ($app && $doc && strpos($app, $doc) === 0) {
        $base = str_replace('\\', '/', substr($app, strlen($doc)));
    }
    return $base = rtrim($base, '/');
}

/** Absolute URL for a path relative to the app root, e.g. nu_url('public/jobs.php'). */
function nu_url($path = '') {
    return nu_base() . '/' . ltrim($path, '/');
}

/** Cache-busted asset URL (content hash), safe to cache for a year. */
function nu_asset($path) {
    static $cache = [];
    if (!isset($cache[$path])) {
        $file = __DIR__ . '/../' . ltrim($path, '/');
        $v = is_file($file) ? substr(md5_file($file), 0, 8) : '0';
        $cache[$path] = nu_url($path) . '?v=' . $v;
    }
    return $cache[$path];
}

function nu_e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Inline SVG icon from the shared sprite (Bootstrap Icons, MIT). */
function nu_icon($name, $class = '') {
    return '<svg class="ic ' . $class . '" aria-hidden="true" focusable="false"><use href="' . nu_asset('public/assets/icons.svg') . '#' . $name . '"></use></svg>';
}

function nu_role() {
    return $_SESSION['role'] ?? null;
}

function nu_is_staff() {
    return in_array(nu_role(), ['manager', 'admin', 'superadmin'], true);
}

function nu_initials($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $i = '';
    $mb = function_exists('mb_substr');
    foreach ($parts as $p) {
        if ($p !== '') $i .= $mb ? mb_strtoupper(mb_substr($p, 0, 1)) : strtoupper(substr($p, 0, 1));
        if (strlen($i) >= 2) break;
    }
    return $i !== '' ? $i : 'N';
}

function nu_money($amount) {
    return '$' . number_format((float) $amount, 2);
}

function nu_date($d, $fmt = 'M j, Y') {
    $t = $d ? strtotime($d) : false;
    return $t ? date($fmt, $t) : '';
}

function nu_ago($d) {
    $t = $d ? strtotime($d) : false;
    if (!$t) return '';
    $s = time() - $t;
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . 'm ago';
    if ($s < 86400) return floor($s / 3600) . 'h ago';
    if ($s < 86400 * 30) return floor($s / 86400) . 'd ago';
    return date('M j, Y', $t);
}

/** Status chip for jobs/applications. Empty status is shown as "pending". */
function nu_status_chip($status) {
    $s = strtolower(trim((string) $status));
    if ($s === '') $s = 'pending';
    $map = [
        'posted' => 'success', 'open' => 'success', 'approved' => 'success', 'accepted' => 'success', 'active' => 'success',
        'pending' => 'warning', 'filled' => 'blue',
        'closed' => '', 'rejected' => 'danger', 'suspended' => 'danger', 'cancelled' => 'danger',
    ];
    $tone = $map[$s] ?? 'blue';
    return '<span class="chip' . ($tone ? ' chip--' . $tone : '') . '">' . nu_e($s) . '</span>';
}

/** Home URL for the current role. */
function nu_home_url() {
    switch (nu_role()) {
        case 'client':   return nu_url('public/client_home.php');
        case 'provider': return nu_url('public/provider_home.php');
        case 'manager': case 'admin': case 'superadmin': return nu_url('public/dashboard.php');
        default: return nu_url('public/index.php');
    }
}

/** Short address line for a job row. */
function nu_place($job) {
    if (!empty($job['location_address'])) {
        $parts = array_map('trim', explode(',', $job['location_address']));
        return implode(', ', array_slice($parts, 0, 2));
    }
    if (isset($job['location_lat'], $job['location_lng']) && $job['location_lat'] !== null && $job['location_lat'] !== '') {
        return number_format((float) $job['location_lat'], 3) . ', ' . number_format((float) $job['location_lng'], 3);
    }
    return 'Location on map';
}

/**
 * Per-page shell config. Keys: title, tab, lean (no Bootstrap), map (false|'defer'|'sync'),
 * cluster, chart, sub (secondary page => back button on mobile).
 */
function nu_page_config() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $rel = ltrim(substr($script, strlen($root)), '/');

    $pages = [
        'index.php'                    => ['title' => 'Seasonal work, simplified', 'tab' => 'home', 'lean' => true, 'landing' => true],
        'public/index.php'             => ['title' => 'Seasonal work, simplified', 'tab' => 'home', 'lean' => true, 'landing' => true],
        'public/auth.php'              => ['title' => 'Welcome', 'tab' => 'login', 'lean' => true],
        'public/login.php'             => ['title' => 'Sign in', 'tab' => 'login', 'lean' => true],
        'public/login_client.php'      => ['title' => 'Client sign in', 'tab' => 'login', 'lean' => true, 'sub' => true],
        'public/login_provider.php'    => ['title' => 'Provider sign in', 'tab' => 'login', 'lean' => true, 'sub' => true],
        'public/login_admin.php'       => ['title' => 'Team sign in', 'tab' => 'login', 'lean' => true, 'sub' => true],
        'public/login_manager.php'     => ['title' => 'Manager sign in', 'tab' => 'login', 'lean' => true, 'sub' => true],
        'public/login_superadmin.php'  => ['title' => 'Superadmin sign in', 'tab' => 'login', 'lean' => true, 'sub' => true],
        'public/register.php'          => ['title' => 'Create account', 'tab' => 'join', 'lean' => true],
        'public/register_client.php'   => ['title' => 'Join as a client', 'tab' => 'join', 'lean' => true, 'sub' => true],
        'public/register_provider.php' => ['title' => 'Join as a provider', 'tab' => 'join', 'lean' => true, 'sub' => true],
        'public/register_admin.php'    => ['title' => 'Add an admin', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/register_manager.php'  => ['title' => 'Add a manager', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/register_superadmin.php' => ['title' => 'Add a superadmin', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/jobs.php'              => ['title' => 'Seasonal jobs', 'tab' => 'jobs', 'lean' => true, 'map' => 'defer'],
        'public/discover_Jobs.php'     => ['title' => 'Jobs map', 'tab' => 'jobs', 'lean' => true, 'map' => 'defer', 'cluster' => true, 'sub' => true],
        'public/provider_home.php'     => ['title' => 'Home', 'tab' => 'home', 'lean' => true],
        'public/client_home.php'       => ['title' => 'Home', 'tab' => 'home', 'lean' => true],
        'public/my_jobs.php'           => ['title' => 'My jobs', 'tab' => 'myjobs', 'lean' => true, 'map' => 'defer'],
        'public/applications.php'      => ['title' => 'My applications', 'tab' => 'applied', 'lean' => true],
        'public/client_applications.php' => ['title' => 'Applicants', 'tab' => 'applicants', 'lean' => true],
        'public/saved_jobs.php'        => ['title' => 'Saved jobs', 'tab' => 'saved', 'lean' => true, 'map' => 'defer'],
        'public/my_activity.php'       => ['title' => 'My activity', 'tab' => 'applied', 'lean' => true, 'map' => 'defer', 'sub' => true],
        'public/manage_applications.php' => ['title' => 'Applications', 'tab' => 'apps', 'lean' => true],
        'public/dashboard.php'         => ['title' => 'Dashboard', 'tab' => 'dash', 'lean' => true, 'chart' => true],
        'public/profile.php'           => ['title' => 'Profile', 'tab' => 'profile', 'lean' => true],
    ];

    $cfg = $pages[$rel] ?? null;
    if ($cfg === null) {
        // Legacy page: keep Bootstrap and sniff which libraries it uses.
        $src = is_file($script) ? (string) @file_get_contents($script) : '';
        $title = ucwords(str_replace(['_', '-'], ' ', basename($rel, '.php')));
        $cfg = [
            'title' => $title ?: 'Nuahn',
            'tab'   => strpos($rel, 'admin/') !== false ? 'dash' : '',
            'sub'   => true,
            'map'   => (strpos($src, 'initMap') !== false || strpos($src, 'L.map') !== false) ? 'sync' : false,
            'icons' => (bool) preg_match('/\bbi bi-|class="bi\b/', $src),
            'bsjs'  => (strpos($src, 'data-bs-') !== false || strpos($src, 'bootstrap.') !== false),
        ];
    }
    $cfg += ['lean' => false, 'map' => false, 'cluster' => false, 'chart' => false, 'icons' => false, 'bsjs' => false, 'sub' => false, 'landing' => false, 'tab' => ''];
    $cfg['rel'] = $rel;
    $cfg['slug'] = preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace('.php', '', $rel)));
    return $cfg;
}

/** Navigation items for the current role: [key, label, url, icon, iconActive, primary]. */
function nu_nav_items() {
    $u = 'nu_url';
    switch (nu_role()) {
        case 'provider':
            return [
                ['home', 'Home', $u('public/provider_home.php'), 'house', 'house-fill'],
                ['jobs', 'Find jobs', $u('public/jobs.php'), 'search', 'search'],
                ['saved', 'Saved', $u('public/saved_jobs.php'), 'bookmark', 'bookmark-fill'],
                ['applied', 'Applied', $u('public/applications.php'), 'clipboard-check', 'clipboard-check-fill'],
                ['profile', 'Profile', $u('public/profile.php'), 'person', 'person-fill'],
            ];
        case 'client':
            return [
                ['home', 'Home', $u('public/client_home.php'), 'house', 'house-fill'],
                ['myjobs', 'My jobs', $u('public/my_jobs.php'), 'briefcase', 'briefcase-fill'],
                ['post', 'Post', $u('public/jobs.php#post'), 'plus-lg', 'plus-lg', true],
                ['applicants', 'Applicants', $u('public/client_applications.php'), 'people', 'people-fill'],
                ['profile', 'Profile', $u('public/profile.php'), 'person', 'person-fill'],
            ];
        case 'manager': case 'admin': case 'superadmin':
            return [
                ['dash', 'Dashboard', $u('public/dashboard.php'), 'grid-1x2', 'grid-1x2-fill'],
                ['apps', 'Applications', $u('public/manage_applications.php'), 'clipboard-check', 'clipboard-check-fill'],
                ['jobs', 'Jobs', $u('public/jobs.php'), 'briefcase', 'briefcase-fill'],
                ['profile', 'Profile', $u('public/profile.php'), 'person', 'person-fill'],
            ];
        default:
            return [
                ['home', 'Home', $u('public/index.php'), 'house', 'house-fill'],
                ['jobs', 'Jobs', $u('public/jobs.php'), 'search', 'search'],
                ['login', 'Sign in', $u('public/login.php'), 'person-circle', 'person-fill'],
                ['join', 'Join', $u('public/register.php'), 'stars', 'stars'],
            ];
    }
}

/** Render a shared auth card header. */
function nu_auth_head($title, $subtitle, $icon = 'person-circle', $navy = false) {
    echo '<div class="auth-card__head">';
    echo '<div class="auth-card__mark' . ($navy ? ' auth-card__mark--navy' : '') . '">' . nu_icon($icon) . '</div>';
    echo '<h1>' . nu_e($title) . '</h1><p>' . nu_e($subtitle) . '</p></div>';
}

/** Left brand panel of the auth layout. */
function nu_auth_aside($headline = 'Seasonal work, made simple.', $copy = 'Post short-term jobs, find trusted providers and keep every application in one calm, fast workspace.') {
    echo '<aside class="auth__aside"><span class="eyebrow" style="color:var(--blue-300)">' . nu_icon('stars') . ' Nuahn Seasonal Hub</span>';
    echo '<h2>' . nu_e($headline) . '</h2><p>' . nu_e($copy) . '</p>';
    echo '<ul class="auth__list">';
    echo '<li>' . nu_icon('geo-alt') . 'Location-based job matching</li>';
    echo '<li>' . nu_icon('shield-check') . 'Verified clients and providers</li>';
    echo '<li>' . nu_icon('lightning-charge-fill') . 'Apply and hire in a few taps</li>';
    echo '</ul></aside>';
}

/** Password input with show/hide toggle. */
function nu_password_field($name, $id, $label, $autocomplete = 'current-password', $extra = '') {
    echo '<label class="field" for="' . nu_e($id) . '"><span class="label">' . nu_e($label) . '</span>';
    echo '<span class="input-icon has-reveal">' . nu_icon('lock');
    echo '<input type="password" class="input" name="' . nu_e($name) . '" id="' . nu_e($id) . '" autocomplete="' . nu_e($autocomplete) . '" required ' . $extra . '>';
    echo '<button type="button" class="reveal-btn" data-reveal-for="' . nu_e($id) . '" aria-label="Show password">' . nu_icon('eye') . '</button>';
    echo '</span></label>';
}

/** Simple login form used by the per-role login pages (fields: email, password). */
function nu_login_form($action, $btnLabel = 'Sign in') {
    $act = $action === null ? '' : ' action="' . nu_e($action) . '"';
    echo '<form method="POST"' . $act . ' class="auth-form">';
    echo '<label class="field" for="email"><span class="label">Email address</span><span class="input-icon">' . nu_icon('envelope');
    echo '<input type="email" name="email" id="email" class="input" placeholder="you@example.com" autocomplete="email" required autofocus></span></label>';
    nu_password_field('password', 'password', 'Password');
    echo '<button type="submit" class="btn btn-primary btn-lg btn-block">' . nu_e($btnLabel) . ' ' . nu_icon('arrow-right') . '</button>';
    echo '</form>';
}

/** Name/email/password form used by the per-role register pages. */
function nu_register_form($btnLabel = 'Create account') {
    echo '<form method="POST" class="auth-form">';
    echo '<label class="field" for="name"><span class="label">Full name</span><span class="input-icon">' . nu_icon('person');
    echo '<input type="text" name="name" id="name" class="input" placeholder="Jane Doe" autocomplete="name" required></span></label>';
    echo '<label class="field" for="email"><span class="label">Email address</span><span class="input-icon">' . nu_icon('envelope');
    echo '<input type="email" name="email" id="email" class="input" placeholder="you@example.com" autocomplete="email" required></span></label>';
    nu_password_field('password', 'password', 'Password', 'new-password');
    echo '<button type="submit" class="btn btn-primary btn-lg btn-block">' . nu_e($btnLabel) . ' ' . nu_icon('arrow-right') . '</button>';
    echo '</form>';
}

/** Job card markup shared by listing pages. $opts: map(bool), footer(html), showStatus(bool) */
function nu_job_card($job, $opts = []) {
    $id = (int) ($job['id'] ?? 0);
    $uid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($opts['uid'] ?? $id));
    $client = $job['client_name'] ?? '';
    $hasCoords = isset($job['location_lat'], $job['location_lng']) && $job['location_lat'] !== null && $job['location_lat'] !== '';
    $i = $opts['i'] ?? 0;
    echo '<article class="card job-card" id="job' . $uid . '" data-reveal style="--i:' . ((int) $i % 6) . '">';
    $hasImage = !empty($job['image']) && is_file(__DIR__ . '/../uploads/jobs/' . basename($job['image']));
    if ($hasImage) {
        echo '<div class="job-card__media">' . nu_icon('image');
        echo '<img src="' . nu_e(nu_url('uploads/jobs/' . $job['image'])) . '" alt="" loading="lazy" decoding="async" width="640" height="280" data-fallback>';
        if (!empty($opts['showStatus'])) echo nu_status_chip($job['status'] ?? '');
        echo '</div>';
    }
    echo '<div class="job-card__body">';
    echo '<div class="job-card__top"><div class="job-card__logo" aria-hidden="true">' . nu_e(nu_initials($client ?: ($job['title'] ?? 'J'))) . '</div>';
    echo '<div><h3 class="job-card__title">' . nu_e($job['title'] ?? '') . '</h3>';
    if ($client !== '') echo '<p class="job-card__by">' . nu_e($client) . '</p>';
    echo '</div><div class="job-card__pay">' . nu_money($job['payment_amount'] ?? 0) . '<small>FIXED PAY</small></div></div>';
    if (!empty($job['description'])) echo '<p class="job-card__desc">' . nu_e($job['description']) . '</p>';
    echo '<div class="job-card__meta">';
    echo '<span>' . nu_icon('geo-alt-fill') . nu_e(nu_place($job)) . '</span>';
    if (!empty($job['created_at'])) echo '<span>' . nu_icon('clock') . nu_e(nu_ago($job['created_at'])) . '</span>';
    if (!$hasImage && !empty($opts['showStatus'])) echo nu_status_chip($job['status'] ?? '');
    if (!empty($opts['meta'])) echo $opts['meta'];
    echo '</div>';
    if (!empty($opts['map']) && $hasCoords) {
        echo '<div class="map-frame" id="mapframe' . $uid . '"><div id="map' . $uid . '" data-lat="' . nu_e($job['location_lat']) . '" data-lng="' . nu_e($job['location_lng']) . '" data-title="' . nu_e($job['title'] ?? '') . '"></div></div>';
    }
    echo '</div>';
    $foot = $opts['footer'] ?? '';
    $mapBtn = (!empty($opts['map']) && $hasCoords)
        ? '<button type="button" class="btn btn-soft btn-sm map-toggle" data-map-toggle="' . $uid . '" aria-expanded="false" aria-controls="mapframe' . $uid . '">' . nu_icon('map') . ' Map</button>'
        : '';
    if ($foot !== '' || $mapBtn !== '') {
        echo '<div class="job-card__foot">' . $mapBtn . '<span class="spacer"></span>' . $foot . '</div>';
    }
    echo '</article>';
}

/** Empty state block. */
function nu_empty($icon, $title, $text, $actionHtml = '') {
    echo '<div class="empty" data-reveal><div class="empty__icon">' . nu_icon($icon) . '</div>';
    echo '<h3>' . nu_e($title) . '</h3><p>' . nu_e($text) . '</p>' . $actionHtml . '</div>';
}

/** Run a read-only COUNT query for display; returns 0 on any error. */
function nu_count($pdo, $sql, $params = []) {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

} // end guard
