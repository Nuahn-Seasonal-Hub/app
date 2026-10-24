<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
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
        'index.php'                    => ['title' => 'Every season. Trusted local pros. One hub.', 'tab' => 'home', 'lean' => true, 'landing' => true],
        'public/index.php'             => ['title' => 'Every season. Trusted local pros. One hub.', 'tab' => 'home', 'lean' => true, 'landing' => true],
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
        'public/jobs.php'              => ['title' => 'Seasonal jobs', 'tab' => 'jobs', 'lean' => true, 'map' => 'lazy'],
        'public/discover_Jobs.php'     => ['title' => 'Jobs map', 'tab' => 'jobs', 'lean' => true, 'map' => 'defer', 'cluster' => true, 'sub' => true],
        'public/provider_home.php'     => ['title' => 'Jobs hub', 'tab' => 'jobs', 'lean' => true],
        'public/client_home.php'       => ['title' => 'Home', 'tab' => 'home', 'lean' => true],
        'public/my_jobs.php'           => ['title' => 'My jobs', 'tab' => 'myjobs', 'lean' => true, 'map' => 'lazy'],
        'public/applications.php'      => ['title' => 'My applications', 'tab' => 'applied', 'lean' => true],
        'public/client_applications.php' => ['title' => 'Applicants', 'tab' => 'applicants', 'lean' => true],
        'public/saved_jobs.php'        => ['title' => 'Saved jobs', 'tab' => 'saved', 'lean' => true, 'map' => 'lazy'],
        'public/my_activity.php'       => ['title' => 'My activity', 'tab' => 'applied', 'lean' => true, 'map' => 'lazy', 'sub' => true],
        'public/manage_applications.php' => ['title' => 'Applications', 'tab' => 'apps', 'lean' => true],
        'public/dashboard.php'         => ['title' => 'Dashboard', 'tab' => 'dash', 'lean' => true, 'chart' => true],
        'public/profile.php'           => ['title' => 'Profile', 'tab' => 'profile', 'lean' => true],
        'public/book.php'              => ['title' => 'Book a service', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/track.php'             => ['title' => 'Track your service', 'tab' => 'bookings', 'lean' => true, 'sub' => true, 'map' => 'defer'],
        'public/bookings.php'          => ['title' => 'My bookings', 'tab' => 'bookings', 'lean' => true],
        'public/messages.php'          => ['title' => 'Messages', 'tab' => 'messages', 'lean' => true],
        'public/chat.php'              => ['title' => 'Chat', 'tab' => 'messages', 'lean' => true, 'sub' => true],
        'public/calendar.php'          => ['title' => 'Calendar', 'tab' => 'calendar', 'lean' => true],
        'public/provider_route.php'    => ['title' => 'Today’s route', 'tab' => 'route', 'lean' => true],
        'public/service.php'           => ['title' => 'Book a service', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/plans.php'             => ['title' => 'Basic or Premium', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/care_plans.php'        => ['title' => 'Your care plans', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/change_appointment.php' => ['title' => 'Change appointment', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/snow_event.php'        => ['title' => 'Snow event', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/activity.php'          => ['title' => 'Activity', 'tab' => 'activity', 'lean' => true],
        'public/mechanics.php'         => ['title' => 'Mobile Mechanics', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/mechanic_request.php'  => ['title' => 'Mechanic request', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/post_task.php'         => ['title' => 'Post a task', 'tab' => 'book', 'lean' => true, 'sub' => true],
        'public/offers.php'            => ['title' => 'Review offers', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/contractor.php'        => ['title' => 'Contractor profile', 'tab' => '', 'lean' => true, 'sub' => true],
        'public/approve.php'           => ['title' => 'Review and approve', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/open_jobs.php'         => ['title' => 'Open jobs near you', 'tab' => 'jobs', 'lean' => true, 'console' => true],
        'public/quote.php'             => ['title' => 'Review quote', 'tab' => 'bookings', 'lean' => true, 'sub' => true],
        'public/provider_dashboard.php' => ['title' => 'Today', 'tab' => 'today', 'lean' => true, 'console' => true],
        'public/operations.php'        => ['title' => 'Operations', 'tab' => 'ops', 'lean' => true, 'console' => true],
        'public/service_settings.php'  => ['title' => 'Service settings', 'tab' => 'ops', 'lean' => true, 'console' => true, 'sub' => true],
        'public/status.php'            => ['title' => 'Job states', 'tab' => '', 'lean' => true, 'sub' => true],
        'public/admin/dashboard.php'   => ['title' => 'Admin overview', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/manage_users.php' => ['title' => 'Manage users', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/manage_admins.php' => ['title' => 'Manage staff', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/subscriptions.php' => ['title' => 'Subscriptions', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/create_plan.php' => ['title' => 'New plan', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/edit_plan.php'   => ['title' => 'Edit plan', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/assign_subscription.php' => ['title' => 'Assign plan', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/notifications.php' => ['title' => 'Notifications', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/notifications_history.php' => ['title' => 'Notification history', 'tab' => 'dash', 'lean' => true, 'sub' => true],
        'public/admin/notifications_analytics.php' => ['title' => 'Notification analytics', 'tab' => 'dash', 'lean' => true, 'sub' => true, 'chart' => true],
        'public/admin/analytics_dashboard.php' => ['title' => 'Analytics', 'tab' => 'dash', 'lean' => true, 'sub' => true, 'chart' => true],
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
    $cfg += ['console' => false, 'lean' => false, 'map' => false, 'cluster' => false, 'chart' => false, 'icons' => false, 'bsjs' => false, 'sub' => false, 'landing' => false, 'tab' => ''];
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
                ['today', 'Today', $u('public/provider_dashboard.php'), 'calendar-check', 'calendar2-check'],
                ['route', 'Route', $u('public/provider_route.php'), 'signpost-split', 'signpost-split-fill'],
                ['jobs', 'Open jobs', $u('public/open_jobs.php'), 'search', 'search'],
                ['messages', 'Messages', $u('public/messages.php'), 'chat-dots', 'chat-dots-fill'],
                ['profile', 'Profile', $u('public/profile.php'), 'person', 'person-fill'],
            ];
        case 'client':
            return [
                ['home', 'Home', $u('public/client_home.php'), 'house', 'house-fill'],
                ['bookings', 'Bookings', $u('public/bookings.php'), 'calendar-check', 'calendar2-check'],
                ['book', 'Book', $u('public/index.php#services'), 'plus-lg', 'plus-lg', true],
                ['activity', 'Activity', $u('public/activity.php'), 'activity', 'activity'],
                ['messages', 'Messages', $u('public/messages.php'), 'chat-dots', 'chat-dots-fill'],
            ];
        case 'manager': case 'admin': case 'superadmin':
            return [
                ['ops', 'Operations', $u('public/operations.php'), 'speedometer2', 'speedometer2'],
                ['dash', 'Dashboard', $u('public/dashboard.php'), 'grid-1x2', 'grid-1x2-fill'],
                ['apps', 'Applications', $u('public/manage_applications.php'), 'clipboard-check', 'clipboard-check-fill'],
                ['jobs', 'Jobs', $u('public/jobs.php'), 'briefcase', 'briefcase-fill'],
                ['profile', 'Profile', $u('public/profile.php'), 'person', 'person-fill'],
            ];
        default:
            return [
                ['home', 'Home', $u('public/index.php'), 'house', 'house-fill'],
                ['services', 'Services', $u('public/index.php#services'), 'grid-1x2', 'grid-1x2-fill'],
                ['jobs', 'Jobs', $u('public/jobs.php'), 'search', 'search'],
                ['login', 'Sign in', $u('public/login.php'), 'person-circle', 'person-fill'],
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
function nu_auth_aside($headline = 'Every season. Trusted local pros. One hub.', $copy = 'A trusted marketplace connecting homeowners with vetted local independent pros. Built for Canadian seasons.') {
    echo '<aside class="auth__aside"><span class="auth__logo">' . nu_logo(40, 'dark') . '</span>';
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

/** Navy page header band used by dashboard-style pages. $actions is trusted HTML. */
function nu_band($icon, $eyebrow, $title, $lead = '', $actions = '') {
    echo '<section class="band"><div class="wrap band__row"><div>';
    echo '<span class="eyebrow">' . nu_icon($icon) . ' ' . nu_e($eyebrow) . '</span>';
    echo '<h1>' . nu_e($title) . '</h1>';
    if ($lead !== '') echo '<p>' . nu_e($lead) . '</p>';
    echo '</div>';
    if ($actions !== '') echo '<div class="row-flex">' . $actions . '</div>';
    echo '</div></section>';
}

/** Shared Chart.js look for the light/navy themes. Call inside DOMContentLoaded. */
function nu_chart_defaults_js() {
    return "var css=getComputedStyle(document.documentElement);function tone(){var d=document.documentElement.getAttribute('data-theme')==='dark';Chart.defaults.color=d?'#8FA5A0':'#5D6B67';Chart.defaults.borderColor=d?'rgba(255,255,255,.08)':'rgba(12,40,36,.07)';}tone();Chart.defaults.font.family=css.getPropertyValue('--font');Chart.defaults.font.weight=600;Chart.defaults.maintainAspectRatio=false;Chart.defaults.plugins.legend.labels.usePointStyle=true;Chart.defaults.plugins.tooltip.backgroundColor='#0A3D3B';Chart.defaults.plugins.tooltip.padding=10;Chart.defaults.plugins.tooltip.cornerRadius=10;var PALETTE=['#11706D','#0A3D3B','#3E9C97','#2A8C42','#C77700','#DC3248','#8CC7C2'];";
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


/* ---------- v2 (client design) helpers ---------- */

/**
 * SAMPLE DATA loader for features that have no backend yet (bookings, plans, weather, tracking,
 * messages, queue, notifications, proof of work). One file per feature in /fixtures.
 * See docs/UI-DATA-HOOKUP.md for the endpoint each one should be replaced with.
 */
function nu_fixture($name) {
    static $cache = [];
    $name = preg_replace('/[^a-z0-9_]/', '', $name);
    if (!isset($cache[$name])) {
        $f = __DIR__ . '/../fixtures/' . $name . '.php';
        $cache[$name] = is_file($f) ? (array) require $f : [];
    }
    return $cache[$name];
}

/** "Sample data" marker shown on screens fed by fixtures. */
function nu_sample_tag($text = 'Sample data') {
    return '<span class="sample-tag" title="Preview content until the backend endpoint is connected">' . nu_icon('info-circle') . nu_e($text) . '</span>';
}

/**
 * Responsive self-hosted photo: AVIF + WebP sources, JPEG fallback, lazy by default.
 * $name = base name in public/assets/img/photos (files are {name}-{w}.{avif|webp}, largest also .jpg).
 */
function nu_photo($name, $alt, $opts = []) {
    $widths = $opts['widths'] ?? [400, 800];
    $sizes  = $opts['sizes'] ?? '(max-width: 640px) 92vw, 320px';
    $ratio  = $opts['ratio'] ?? (4 / 3);
    $eager  = !empty($opts['eager']);
    $class  = $opts['class'] ?? '';
    $dir = 'public/assets/img/photos/';
    $set = function ($ext) use ($widths, $name, $dir) {
        $o = [];
        foreach ($widths as $w) { $o[] = nu_url($dir . $name . '-' . $w . '.' . $ext) . ' ' . $w . 'w'; }
        return implode(', ', $o);
    };
    $max = max($widths);
    $jpgW = $max;
    $h = (int) round($max / $ratio);
    $out = '<picture class="' . nu_e($class) . '">';
    $out .= '<source type="image/avif" srcset="' . nu_e($set('avif')) . '" sizes="' . nu_e($sizes) . '">';
    $out .= '<source type="image/webp" srcset="' . nu_e($set('webp')) . '" sizes="' . nu_e($sizes) . '">';
    $jpg = is_file(__DIR__ . '/../' . $dir . $name . '-' . $jpgW . '.jpg') ? $name . '-' . $jpgW . '.jpg' : $name . '-' . $jpgW . '.webp';
    $out .= '<img src="' . nu_e(nu_url($dir . $jpg)) . '" alt="' . nu_e($alt) . '" width="' . $max . '" height="' . $h . '"'
          . ($eager ? ' fetchpriority="high" decoding="async"' : ' loading="lazy" decoding="async"') . '>';
    return $out . '</picture>';
}

/**
 * Seasonal theme. Each season has one primary colour from the brand sheet; the whole UI follows it.
 * Season by date (Alberta, northern hemisphere): Winter Dec-Feb, Spring Mar-May, Summer Jun-Aug, Fall Sep-Nov.
 * Review override: ?season=winter|spring|summer|fall sets a sticky cookie; ?season=auto clears it. Presentation only.
 */
function nu_season() {
    static $s = null;
    if ($s !== null) { return $s; }
    $all = ['winter', 'spring', 'summer', 'fall'];
    $q = isset($_GET['season']) ? strtolower((string) $_GET['season']) : '';
    if (in_array($q, $all, true)) {
        if (!headers_sent()) { setcookie('nu_season', $q, ['expires' => time() + 30 * 86400, 'path' => '/', 'samesite' => 'Lax']); }
        return $s = $q;
    }
    if ($q === 'auto') {
        if (!headers_sent()) { setcookie('nu_season', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']); }
    } elseif (isset($_COOKIE['nu_season']) && in_array($_COOKIE['nu_season'], $all, true)) {
        return $s = $_COOKIE['nu_season'];
    }
    $m = (int) (new DateTime('now', new DateTimeZone('America/Edmonton')))->format('n');
    return $s = ($m === 12 || $m <= 2) ? 'winter' : ($m <= 5 ? 'spring' : ($m <= 8 ? 'summer' : 'fall'));
}

/** Season palette: the brand colour plus deeper shades of the same hue, used where text needs contrast. */
function nu_season_palette($season = null) {
    $p = [
        'winter' => ['brand' => '#4FB3E8', 'text' => '#1678AB', 'deep' => '#0F5073', 'navy' => '#08293A', 'night' => '#061C28', 'tint' => '#EDF7FD', 'tint2' => '#DBEFFA', 'soft' => '#8ECEF0'],
        'spring' => ['brand' => '#6BCB3D', 'text' => '#418022', 'deep' => '#2B5617', 'navy' => '#1A340E', 'night' => '#12240A', 'tint' => '#F3FBEF', 'tint2' => '#E6F6DF', 'soft' => '#B2E49A'],
        'summer' => ['brand' => '#F0B400', 'text' => '#8F6B00', 'deep' => '#614900', 'navy' => '#3A2E08', 'night' => '#282006', 'tint' => '#FFFAEB', 'tint2' => '#FFF5D6', 'soft' => '#FFDF80'],
        'fall' => ['brand' => '#F27A1A', 'text' => '#B5560A', 'deep' => '#7B3B07', 'navy' => '#3A1E08', 'night' => '#281506', 'tint' => '#FEF4EC', 'tint2' => '#FDE9D8', 'soft' => '#F8B986'],
    ];
    return $p[$season ?: nu_season()];
}

/**
 * Brand mark only (four-petal mark), for compact places such as the mobile bar. $h = display height in px.
 * One small webp (96 px source, 256 px for high-density), explicit width and height so nothing shifts.
 */
function nu_logo_mark($h = 34) {
    $h = (int) $h; $w = (int) round($h * 256 / 250);
    return '<img class="logo-mark" src="' . nu_e(nu_asset('public/assets/img/mark-96.webp')) . '" srcset="' . nu_e(nu_asset('public/assets/img/mark-96.webp')) . ' 96w, ' . nu_e(nu_asset('public/assets/img/mark-256.webp')) . ' 256w"'
        . ' sizes="' . $w . 'px" width="' . $w . '" height="' . $h . '" alt="Nuahn Seasonal Hub" decoding="async">';
}

/**
 * Brand lockup: the four-petal mark (image) beside the words "Nuahn Seasonal Hub" as real text in the page font
 * (Outfit SemiBold, the brand font). $h = mark height in px; the text scales with it. The text follows currentColor: ink on
 * light surfaces, white inside dark areas (.logo--dark, the footer, sign-in aside, console and the dark theme).
 * $mode: 'auto' (inherits, theme CSS decides), 'dark' (always white text, for dark teal surfaces) or 'light'.
 */
function nu_logo($h = 34, $mode = 'auto') {
    $h = (int) $h; $w = (int) round($h * 256 / 250); $fs = round($h * 0.52, 1);
    $m = nu_e(nu_asset('public/assets/img/mark-96.webp')); $m2 = nu_e(nu_asset('public/assets/img/mark-256.webp'));
    return '<span class="logo logo--' . nu_e($mode) . '"><img class="logo-mark" src="' . $m . '" srcset="' . $m . ' 96w, ' . $m2 . ' 256w" sizes="' . $w . 'px" width="' . $w . '" height="' . $h . '" alt="" decoding="async">'
        . '<span class="logo__text" style="font-size:' . $fs . 'px">Nuahn Seasonal Hub</span></span>';
}

/** Legacy (v2) service slugs -> current slugs, so old links keep working. */
function nu_service_slug($slug) {
    $alias = ['garbage-can-cleaning' => 'garbage-bin-cleaning', 'mobile-detailing' => 'car-wash', 'summer-cleanup' => 'fall-cleanup', 'snow' => 'snow-removal'];
    $slug = (string) $slug;
    return $alias[$slug] ?? $slug;
}
/** Service page URL (doc2 p2-7: one page per service with the booking form). */
/** The service detail page (about, what is included, direct-price sample). Locked services only have this page. */
function nu_service_page_url($slug) { return nu_url('public/service.php') . '?s=' . rawurlencode(nu_service_slug($slug)); }
/** Main "start" link for a service card: live services go straight into the marketplace flow, locked ones to their coming-soon page. */
function nu_book_url($slug) { return nu_start_url($slug); }
/**
 * Where a live service's main "start" button goes in the marketplace model: Snow Removal opens the task builder,
 * Mobile Mechanics opens the pathway chooser. Locked services keep the coming-soon service page.
 */
function nu_start_url($slug) {
    $s = nu_service_slug($slug);
    if ($s === 'snow-removal') return nu_url('public/post_task.php');
    if ($s === 'mobile-mechanics') return nu_url('public/mechanics.php');
    return nu_service_page_url($s);
}
/** Service record for a slug (aliases resolved), or null. */
function nu_service($slug) { $all = nu_fixture('services'); return $all[nu_service_slug($slug)] ?? null; }

/** True when a service is bookable ('live'). Unknown slugs count as not live. */
function nu_service_live($slug) { $sv = nu_service($slug); return $sv !== null && ($sv['status'] ?? 'live') === 'live'; }
/** Services filtered by status, keeping fixture order: nu_services_by_status('live') | ('coming_soon'). */
function nu_services_by_status($status) {
    return array_filter(nu_fixture('services'), function ($sv) use ($status) { return ($sv['status'] ?? 'live') === $status; });
}
/** "Coming soon" badge (locked services). */
function nu_soon_chip($label = 'Coming soon') {
    return '<span class="chip chip--soon">' . nu_icon('lock-fill') . nu_e($label) . '</span>';
}

/**
 * Coming-soon state for a locked service: what to expect + a UI-only "Notify me when it launches" form.
 * Used by public/service.php and public/book.php (direct hits). Nothing is stored or sent.
 * Sir Paul: POST /api/notify {email, postal_code, service} when the endpoint exists (docs/UI-DATA-HOOKUP.md).
 */
function nu_soon_panel($slug, $sv) {
    $done = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['notify_email'])
        && filter_var(trim((string) $_POST['notify_email']), FILTER_VALIDATE_EMAIL);
    $bad = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['notify_email']) && !$done;
    $expect = [
        ['shield-check', 'Vetted local independent pros', 'Every pro on Nuahn is reviewed before they can take jobs in your area.'],
        ['cash-coin', 'Clear price before you confirm', 'See the price and the arrival window first. Nothing is charged until you confirm.'],
        ['camera', 'Proof of work', 'Photos and a checklist land in your activity feed when the job is done.'],
    ];
    $live = nu_services_by_status('live');
    ob_start(); ?>
  <section class="soonp" aria-labelledby="soonp-h">
    <div class="sv-card soonp__info">
      <p class="soonp__tag"><?= nu_soon_chip() ?> <span>Not bookable yet</span></p>
      <h2 id="soonp-h" class="soonp__h">What to expect from <?= nu_e($sv['name']) ?></h2>
      <p class="soonp__lead"><?= nu_e($sv['blurb']) ?> We are opening Nuahn with <?= nu_e(implode(' and ', array_map(function ($x) { return $x['name']; }, $live))) ?> first. This service follows.</p>
      <ul class="soonp__list">
<?php foreach ($expect as $e): ?>
        <li><span class="soonp__ic"><?= nu_icon($e[0]) ?></span><div><b><?= nu_e($e[1]) ?></b><span><?= nu_e($e[2]) ?></span></div></li>
<?php endforeach; ?>
      </ul>
      <p class="soonp__meta"><?= nu_icon('calendar3') ?> Season: <?= nu_e($sv['season']) ?> <?= nu_sample_tag('Sample details') ?></p>
    </div>
    <div class="sv-card soonp__form">
      <h2 class="soonp__h">Notify me when it launches</h2>
      <p class="soonp__lead">Leave your email and we will tell you the day <?= nu_e($sv['name']) ?> opens in your area.</p>
      <form class="soonp-form" method="post" action="#notify" id="notify" novalidate data-notify<?= $done ? ' hidden' : '' ?>>
        <label class="label" for="soonp-email">Email address</label>
        <input class="input" id="soonp-email" name="notify_email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" required>
        <label class="label" for="soonp-zip">Postal code <span class="muted">(optional)</span></label>
        <input class="input" id="soonp-zip" name="notify_postal" type="text" autocomplete="postal-code" placeholder="T5J 0N3" maxlength="7">
        <p class="soonp-err" role="alert" data-err<?= $bad ? '' : ' hidden' ?>>Please enter a valid email address.</p>
        <button class="btn btn-primary btn-lg btn-block" type="submit"><?= nu_icon('bell') ?> Notify me when it launches</button>
        <p class="soonp__fine">We only use this to tell you about the launch. <?= nu_sample_tag('Preview only, nothing is stored') ?></p>
      </form>
      <div class="soonp-thanks" role="status" data-thanks<?= $done ? '' : ' hidden' ?>>
        <span class="soonp-thanks__ic"><?= nu_icon('check-lg') ?></span>
        <div><b>Thanks, we'll be in touch.</b><span>We will let you know when <?= nu_e($sv['name']) ?> is ready to book.</span></div>
      </div>
      <div class="soonp__alt">
        <p>Need help now? Book one of the services launching first:</p>
<?php foreach ($live as $ls => $lv): ?>
        <a class="btn btn-outline btn-sm" href="<?= nu_e(nu_book_url($ls)) ?>"><?= nu_icon($lv['icon']) ?> <?= nu_e($lv['name']) ?></a>
<?php endforeach; ?>
      </div>
    </div>
  </section>
  <script>
  (function () {
    var f = document.querySelector('[data-notify]'), t = document.querySelector('[data-thanks]'), e = document.querySelector('[data-err]');
    if (!f) return;
    f.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var v = f.elements.notify_email.value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { e.hidden = false; f.elements.notify_email.focus(); f.elements.notify_email.setAttribute('aria-invalid', 'true'); return; }
      e.hidden = true; f.hidden = true; t.hidden = false; t.setAttribute('tabindex', '-1'); t.focus();
    });
  })();
  </script>
<?php return ob_get_clean();
}

/**
 * Shared booking / job status vocabulary (doc1 p8 "State and payment rules", doc2 p21).
 * One value drives every panel for customer, provider and admin. Returns [label, tone].
 */
function nu_booking_status($s) {
    $m = [
        'requested' => ['Requested', 'warning'], 'searching' => ['Searching for provider', 'warning'], 'no_provider' => ['No provider available', 'danger'],
        'quote_pending' => ['Quote pending', 'blue'], 'awaiting_quote' => ['Awaiting quote', 'blue'], 'reschedule_pending' => ['Reschedule pending', 'warning'], 'confirmed' => ['Confirmed', 'blue'], 'assigned' => ['Assigned', 'blue'],
        'unassigned' => ['Unassigned', 'warning'], 'change_requested' => ['Change requested', 'warning'],
        'on_the_way' => ['On the way', 'success'], 'arrived' => ['Arrived', 'success'], 'in_progress' => ['In progress', 'success'],
        'paused' => ['Paused', 'warning'], 'delayed' => ['Delayed', 'danger'], 'weather_review' => ['Weather review', 'danger'],
        'completed' => ['Completed', 'navy'], 'disputed' => ['Disputed', 'danger'], 'closed' => ['Closed', ''], 'cancelled' => ['Cancelled', 'danger'],
        'reminder_due' => ['Reminder due', 'blue'], 'skipped' => ['Skipped', ''], 'next' => ['Up next', 'blue'], 'queued' => ['Queued', ''],
        'available' => ['Available', 'success'], 'busy' => ['Busy', 'danger'],
        'open_for_bids' => ['Open for bids', 'blue'], 'pending_approval' => ['Pending your approval', 'warning'], 'approved' => ['Approved', 'navy'],
        'funds_held' => ['Confirmed · funds held', 'blue'], 'review' => ['In review', 'warning'], 'open' => ['Open', 'warning'],
    ];
    return $m[$s] ?? [ucfirst(str_replace('_', ' ', (string) $s)), ''];
}
function nu_booking_chip($s, $dot = true) {
    [$l, $t] = nu_booking_status($s);
    return '<span class="chip' . ($t ? ' chip--' . $t : '') . ($dot ? ' chip--dot' : '') . '">' . nu_e($l) . '</span>';
}
/** Where a booking row opens, by job state (marketplace state machine). */
function nu_booking_href($b) {
    $id = rawurlencode($b['id']);
    switch ($b['status']) {
        case 'open_for_bids': return nu_url('public/offers.php') . '?state=many';
        case 'quote_pending': return nu_url('public/quote.php');
        case 'change_requested': return nu_url('public/snow_event.php') . '?state=pending';
        case 'pending_approval': return nu_url('public/approve.php') . '?id=' . $id;
        case 'approved': case 'completed': return nu_url('public/activity.php');
        case 'disputed': return nu_url('public/activity.php') . '#feedback';
        default: return nu_url('public/track.php') . '?id=' . $id;
    }
}
/** Payment is a separate state from job progress (doc2 p21). */
function nu_payment_chip($s) {
    // Marketplace payment states: Unpaid, Authorized (funds held), Captured (paid out), Refunded, Disputed.
    $m = ['unpaid' => ['Unpaid', '', 'credit-card'], 'authorized' => ['Authorized · funds held', 'blue', 'lock-fill'], 'held' => ['Authorized · funds held', 'blue', 'lock-fill'],
          'paid' => ['Captured · paid out', 'success', 'check-circle-fill'], 'captured' => ['Captured · paid out', 'success', 'check-circle-fill'],
          'failed' => ['Payment failed', 'danger', 'exclamation-circle'], 'disputed' => ['Disputed · funds held', 'danger', 'flag-fill'],
          'partially_refunded' => ['Partially refunded', 'warning', 'arrow-repeat'], 'refunded' => ['Refunded', '', 'arrow-repeat']];
    [$l, $t, $ic] = $m[$s] ?? [ucfirst((string) $s), '', 'credit-card'];
    return '<span class="chip chip--pay' . ($t ? ' chip--' . $t : '') . '">' . nu_icon($ic) . nu_e($l) . '</span>';
}
/** Plain-text label for a payment state (for lists and aria text). */
function nu_payment_label($s) { return trim(strip_tags(nu_payment_chip($s))); }
/** Payment state implied by a job state in the marketplace state machine (they are still stored separately). */
function nu_job_payment($state) {
    if (in_array($state, ['requested', 'open_for_bids', 'quote_pending', 'searching'], true)) return 'unpaid';
    if ($state === 'approved' || $state === 'completed') return 'captured';
    if ($state === 'disputed') return 'disputed';
    return 'authorized';
}
/**
 * Job state machine (Requested → Open for bids → Confirmed with funds held → On the way → Arrived → In progress →
 * Completed, pending approval → Approved or Disputed). $key is a fixtures/marketplace.php 'states' key or 'disputed'.
 * mode 'bar' = compact 8-segment bar with the current state; mode 'list' = the full timeline. Job status and payment
 * status stay separate: the payment chip is rendered next to it by the caller.
 */
function nu_job_machine($key, $mode = 'bar', $times = []) {
    $st = nu_fixture('marketplace')['states'];
    $keys = array_keys($st);
    $disputed = $key === 'disputed';
    if ($disputed) $key = 'pending_approval';
    $alias = ['completed' => 'pending_approval', 'quote_pending' => 'open_for_bids', 'awaiting_quote' => 'open_for_bids', 'searching' => 'open_for_bids', 'assigned' => 'confirmed', 'change_requested' => 'confirmed', 'weather_review' => 'confirmed', 'paused' => 'in_progress', 'closed' => 'approved'];
    if (isset($alias[$key])) $key = $alias[$key];
    $at = array_search($key, $keys, true); if ($at === false) $at = 0;
    $labels = $st; if ($disputed) $labels['approved'] = 'Disputed';
    $final = $disputed ? ' is-disputed' : '';
    if ($mode === 'bar') {
        $o = '<div class="jsm' . $final . '" role="group" aria-label="Job progress"><div class="jsm__bar" aria-hidden="true">';
        foreach ($keys as $i => $k) $o .= '<i class="' . ($i < $at ? 'is-done' : ($i === $at ? 'is-now' : '')) . '"></i>';
        $o .= '</div><p class="jsm__now"><b>' . nu_e($disputed ? 'Disputed · under review' : $labels[$key]) . '</b><span>Step ' . ($at + 1) . ' of ' . count($keys) . '</span></p></div>';
        return $o;
    }
    $o = '<ol class="jsm-list' . $final . '" aria-label="Job progress">';
    foreach ($keys as $i => $k) {
        $c = $i < $at ? 'is-done' : ($i === $at ? 'is-now' : '');
        $o .= '<li class="' . $c . '"' . ($i === $at ? ' aria-current="step"' : '') . '><span class="jsm-list__dot">' . ($i <= $at ? nu_icon('check-lg') : '') . '</span><span class="jsm-list__l">' . nu_e($labels[$k]) . '</span>'
            . (!empty($times[$k]) ? '<small>' . nu_e($times[$k]) . '</small>' : '') . '</li>';
    }
    return $o . '</ol>';
}

/** Independent contractor lookup (fixtures/contractors.php), or null. */
function nu_contractor($id) { $all = nu_fixture('contractors'); return $all[$id] ?? null; }
/** Initials avatar for a contractor (no photo, so nothing to load). */
function nu_avatar($c, $size = 'md') {
    return '<span class="avt avt--' . nu_e($c['tone'] ?? 'teal') . ' avt--' . nu_e($size) . '" aria-hidden="true">' . nu_e($c['initials']) . '</span>';
}
/** Trust badges. $compact = icons + short text only for the first three. */
function nu_badges($c, $compact = false) {
    $def = ['verified' => ['Verified', 'patch-check-fill'], 'insured' => ['Insured', 'shield-check'], 'id_checked' => ['ID checked', 'person-vcard'], 'certified' => ['Certified', 'award']];
    $o = '<span class="bdgs">';
    foreach ($c['badges'] as $b) { if (!isset($def[$b])) continue; $o .= '<span class="bdg bdg--' . $b . '">' . nu_icon($def[$b][1]) . $def[$b][0] . '</span>'; }
    return $o . '</span>';
}
/** Star rating line, e.g. "★ 4.9 (212)". */
function nu_rating($c) {
    return '<span class="rate">' . nu_icon('star-fill') . '<b>' . number_format($c['rating'], 1) . '</b><small>(' . (int) $c['reviews'] . ')</small></span>';
}
/** Trust copy about payments. UI copy only: no processor names or logos. */
function nu_trust_note($variant = 'held') {
    $t = [
        'held' => ['Funds held securely until you approve the work', 'Your card details never touch Nuahn. They go straight to our secure payment processor and we only keep a token. You are charged only when you approve the finished job.'],
        'card' => ['Payment is handled by a secure processor', 'Card details are entered in the processor’s secure form, never on Nuahn’s servers. We store a token, not your card number.'],
    ][$variant] ?? ['', ''];
    return '<p class="trust"><span class="trust__ic">' . nu_icon('lock-fill') . '</span><span><b>' . nu_e($t[0]) . '</b><small>' . nu_e($t[1]) . '</small></span></p>';
}

/**
 * Horizontal progress stepper. $steps = [[label, time], ...]; $at = index of the current step (-1 = none yet).
 * Done steps get a check, the current step a filled ring.
 */
function nu_steps($steps, $at, $class = '') {
    $o = '<ol class="hsteps ' . nu_e($class) . '">';
    foreach ($steps as $i => $st) {
        $label = is_array($st) ? $st[0] : $st; $time = is_array($st) ? ($st[1] ?? '') : '';
        $cls = $i < $at ? 'is-done' : ($i === $at ? 'is-current' : '');
        $o .= '<li class="' . $cls . '"' . ($i === $at ? ' aria-current="step"' : '') . '><span class="hsteps__dot">' . ($i < $at || $i === $at ? nu_icon('check-lg') : '') . '</span>'
            . '<span class="hsteps__l">' . nu_e($label) . '</span>' . ($time !== '' ? '<span class="hsteps__t">' . nu_e($time) . '</span>' : '') . '</li>';
    }
    return $o . '</ol>';
}

/**
 * Property / location picker: address field, "Use my location", and a draggable pin on a self-contained map
 * (inline SVG, no external scripts or tiles). The pin also moves with the arrow keys.
 *
 * HOOK FOR GOOGLE MAPS OR MAPBOX (Sir Paul): define window.NuahnMapProvider BEFORE public/assets/map-picker.js runs:
 *   { mount(el, {lat, lng, onMove(lat, lng)}) -> {setPosition(lat, lng)},   // draw the real map inside el
 *     geocode(addressText) -> Promise<{lat, lng, label}>,                     // address box "Find"
 *     reverse(lat, lng) -> Promise<string> }                                  // pin dropped -> address text
 * Without it, the built-in static sample map is used. Hidden inputs <name>_lat / <name>_lng carry the pin position.
 * Never put an API key in this file; read it from server config.
 */
function nu_map_picker($o = []) {
    $o += ['name' => 'loc', 'address' => '', 'lat' => 53.4891, 'lng' => -113.5262, 'label' => 'Service location', 'placeholder' => 'Enter the property address'];
    $n = nu_e($o['name']);
    ob_start(); ?>
<div class="mp" data-map-picker data-lat="<?= (float) $o['lat'] ?>" data-lng="<?= (float) $o['lng'] ?>">
  <label class="label" for="<?= $n ?>-addr"><?= nu_e($o['label']) ?></label>
  <div class="mp__row">
    <span class="mp__in"><?= nu_icon('geo-alt') ?><input class="input" id="<?= $n ?>-addr" name="<?= $n ?>_address" value="<?= nu_e($o['address']) ?>" placeholder="<?= nu_e($o['placeholder']) ?>" autocomplete="street-address" data-mp-addr></span>
    <button class="btn btn-soft btn-sm" type="button" data-mp-find>Find</button>
  </div>
  <div class="mp__map" data-mp-map>
    <svg class="mp__svg" viewBox="0 0 400 250" role="img" aria-label="Sample map of the neighbourhood. Drag the pin to your property." preserveAspectRatio="none">
      <rect width="400" height="250" fill="var(--map-bg)"/>
      <path d="M0 0 H190 C 170 40, 120 52, 70 44 S 10 60, 0 90Z" fill="var(--map-park)" opacity=".7"/>
      <path d="M300 250 C 310 200, 350 190, 400 196 V250Z" fill="var(--map-park)" opacity=".6"/>
      <path d="M-10 190 C 80 160, 150 215, 240 170 S 360 120, 410 150" fill="none" stroke="var(--map-water)" stroke-width="12" stroke-linecap="round"/>
      <g stroke="var(--map-road)" fill="none" stroke-linecap="round"><path stroke-width="8" d="M0 110 H400 M214 0 V250"/><path stroke-width="4" d="M0 52 H400 M0 168 H400 M64 0 V250 M138 0 V250 M292 0 V250 M352 0 V250 M0 210 H400"/><path stroke-width="2" d="M0 80 H400 M0 140 H400 M100 0 V250 M178 0 V250 M254 0 V250 M322 0 V250"/></g>
      <g fill="#fff" opacity=".8"><rect x="226" y="60" width="22" height="14" rx="2"/><rect x="262" y="60" width="22" height="14" rx="2"/><rect x="226" y="84" width="22" height="14" rx="2"/><rect x="262" y="120" width="22" height="14" rx="2"/><rect x="226" y="120" width="22" height="14" rx="2"/><rect x="150" y="60" width="22" height="14" rx="2"/><rect x="150" y="120" width="22" height="14" rx="2"/><rect x="76" y="120" width="22" height="14" rx="2"/><rect x="76" y="60" width="22" height="14" rx="2"/></g>
      <g font-family="system-ui, sans-serif" font-size="8" fill="var(--muted)" font-weight="600" letter-spacing=".06em"><text x="220" y="106">MAPLE GROVE DR</text><text x="8" y="106">WHITEMUD DR</text><text x="70" y="48">PARK</text></g>
    </svg>
    <button class="mp__pin" type="button" data-mp-pin aria-label="Property pin. Drag it, or use the arrow keys, to move it." style="left:50%;top:50%"><span class="mp__pulse"></span><?= nu_icon('geo-alt-fill') ?></button>
    <button class="mp__loc" type="button" data-mp-locate><?= nu_icon('crosshair') ?> Use my location</button>
  </div>
  <p class="mp__msg" data-mp-msg role="status"><?= nu_icon('pin-map') ?> <span data-mp-text>Drag the pin onto your driveway.</span> <?= nu_sample_tag('Sample map') ?></p>
  <input type="hidden" name="<?= $n ?>_lat" data-mp-lat value="<?= (float) $o['lat'] ?>"><input type="hidden" name="<?= $n ?>_lng" data-mp-lng value="<?= (float) $o['lng'] ?>">
</div>
<?php return ob_get_clean();
}

/**
 * Lightweight illustrative route map (inline SVG, no tiles). Used on service pages where the mockups show a
 * small trip map. The live map on track.php uses real tiles. $v picks one of three sample routes.
 */
function nu_mini_map($v = 0, $label = 'Map preview of the contractor’s route to your home') {
    $routes = [
        'M40 132 C 80 132, 96 104, 132 104 S 176 76, 206 76 S 252 60, 268 44',
        'M34 52 L 92 52 L 92 98 L 168 98 L 168 128 L 236 128 L 236 70 L 280 70',
        'M30 140 C 70 120, 90 70, 140 74 S 210 120, 250 92 S 270 50, 284 40',
    ];
    $r = $routes[$v % 3];
    preg_match_all('/-?\d+(?:\.\d+)?/', $r, $m); $n = $m[0];
    $sx = $n[0]; $sy = $n[1]; $ex = $n[count($n) - 2]; $ey = $n[count($n) - 1];
    return '<svg class="minimap" viewBox="0 0 320 180" role="img" aria-label="' . nu_e($label) . '" preserveAspectRatio="xMidYMid slice">'
        . '<rect width="320" height="180" fill="var(--map-bg)"/>'
        . '<path d="M200 0 C 230 40, 300 40, 320 30 V0Z M0 150 C 40 140, 60 180, 70 180 H0Z" fill="var(--map-park)"/>'
        . '<path d="M262 120 c 18 -6 40 6 42 26 s -22 30 -40 22 s -18 -42 -2 -48z" fill="var(--map-water)"/>'
        . '<g stroke="var(--map-road)" stroke-width="7" fill="none" stroke-linecap="round"><path d="M0 52 H320 M0 104 H320 M0 150 H320 M92 0 V180 M168 0 V180 M236 0 V180"/></g>'
        . '<g stroke="var(--map-road)" stroke-width="3" fill="none" opacity=".85"><path d="M0 78 H320 M0 128 H320 M40 0 V180 M128 0 V180 M204 0 V180 M282 0 V180"/></g>'
        . '<path d="' . $r . '" fill="none" stroke="var(--teal)" stroke-width="3.2" stroke-dasharray="2 6" stroke-linecap="round"/>'
        . '<g transform="translate(' . $sx . ' ' . $sy . ')"><circle r="13" fill="var(--teal)" opacity=".18"/><rect x="-10" y="-7" width="20" height="13" rx="3" fill="var(--pine)"/><rect x="4" y="-4" width="5" height="5" rx="1" fill="#CFE9E5"/></g>'
        . '<g transform="translate(' . $ex . ' ' . $ey . ')"><circle r="15" fill="var(--teal)" opacity=".2"/><circle r="10" fill="var(--pine)"/><path d="M-5 1 L0 -4 L5 1 V5 H-5Z" fill="#fff"/></g>'
        . '</svg>';
}

/**
 * Render one service-form field (fixtures/services.php form[] / fixtures/mechanic.php fields[]).
 * Presentation only: names are what Sir Paul's booking endpoint should accept.
 */
function nu_form_field($f, $idp = 'f') {
    $name = $f['name'] ?? 'field'; $id = $idp . '-' . $name; $label = $f['label'] ?? '';
    $ic = !empty($f['icon']) ? nu_icon($f['icon']) : '';
    $val = $f['value'] ?? null; $o = '';
    $lab = function ($for = null) use ($label, $ic, $f) {
        if ($label === '') return '';
        $tag = $for ? '<label class="label" for="' . nu_e($for) . '">' : '<span class="label">';
        return $tag . ($ic && empty($f['icon_in']) ? $ic : '') . nu_e($label) . ($for ? '</label>' : '</span>');
    };
    switch ($f['type']) {
        case 'address':
            $v = !empty($f['prefix']) ? $f['prefix'] . ' · Edmonton, AB' : '123 Maple Grove Drive, Edmonton, AB';
            $o .= $lab($id) . '<span class="input-icon">' . nu_icon('geo-alt-fill') . '<input class="input" id="' . $id . '" name="' . nu_e($name) . '" value="' . nu_e($v) . '" autocomplete="street-address"></span>';
            if (!empty($f['note'])) $o .= '<p class="fld-note">' . nu_icon('file-text') . nu_e($f['note']) . '</p>';
            break;
        case 'select':
            $o .= $lab($id) . '<select class="input" id="' . $id . '" name="' . nu_e($name) . '">';
            foreach ($f['options'] as $op) { $o .= '<option' . ((string) $val === (string) $op ? ' selected' : '') . '>' . nu_e($op) . '</option>'; }
            $o .= '</select>';
            if (!empty($f['links'])) $o .= '<p class="fld-links"><a href="' . nu_e(nu_url('public/mechanics.php')) . '#roadside">Roadside help ' . nu_icon('arrow-right') . '</a><a href="' . nu_e(nu_url('public/mechanics.php')) . '#services">Browse all repairs ' . nu_icon('arrow-right') . '</a></p>';
            break;
        case 'checks':
            $o .= '<fieldset class="fset"><legend class="label">' . $ic . nu_e($label) . '</legend><div class="checks' . (!empty($f['inline']) ? ' checks--inline' : '') . (!empty($f['cols']) ? ' checks--2' : '') . '">';
            foreach ($f['options'] as $op) {
                [$t, $d] = array_pad(explode('|', $op, 2), 2, '');
                $on = in_array($t, (array) $val, true);
                $o .= '<label class="check"><input type="checkbox" name="' . nu_e($name) . '[]" value="' . nu_e($t) . '"' . ($on ? ' checked' : '') . '><span class="check__box">' . nu_icon('check-lg') . '</span><span class="check__t">' . nu_e($t) . ($d !== '' ? '<small>' . nu_e($d) . '</small>' : '') . '</span></label>';
            }
            $o .= '</div></fieldset>';
            break;
        case 'radios':
            $o .= '<fieldset class="fset"><legend class="label">' . $ic . nu_e($label) . '</legend><div class="radios' . (!empty($f['pills']) ? ' radios--pills' : '') . '">';
            foreach ($f['options'] as $op) {
                $o .= '<label class="radio"><input type="radio" name="' . nu_e($name) . '" value="' . nu_e($op) . '"' . ((string) $val === (string) $op ? ' checked' : '') . '><span>' . nu_e($op) . '</span></label>';
            }
            $o .= '</div></fieldset>';
            break;
        case 'cards':
            $o .= '<fieldset class="fset"><legend class="label">' . nu_e($label) . '</legend><div class="ocards">';
            foreach ($f['options'] as $op) {
                $o .= '<label class="ocard"><input type="radio" name="' . nu_e($name) . '" value="' . nu_e($op[0]) . '"' . ($val === $op[0] ? ' checked' : '') . '><span>' . nu_icon($op[1]) . '<b>' . nu_e($op[0]) . '</b><i class="ocard__r" aria-hidden="true"></i></span></label>';
            }
            $o .= '</div></fieldset>';
            break;
        case 'utility':
            $w = $f['water'] ?? 'provider'; $p = $f['power'] ?? 'provider';
            $o .= '<div class="util" data-util>';
            $o .= '<fieldset class="fset"><legend class="label">Water supply</legend><div class="radios"><label class="radio"><input type="radio" name="water_source" value="customer"' . ($w === 'customer' ? ' checked' : '') . '><span>Use my water</span></label><label class="radio"><input type="radio" name="water_source" value="provider"' . ($w === 'provider' ? ' checked' : '') . '><span>Provider brings water</span></label></div></fieldset>';
            $o .= '<fieldset class="fset"><legend class="label">' . nu_icon('plug') . 'Electricity supply</legend><div class="radios"><label class="radio"><input type="radio" name="power_source" value="customer"' . ($p === 'customer' ? ' checked' : '') . '><span>Use my electricity</span></label><label class="radio"><input type="radio" name="power_source" value="provider"' . ($p === 'provider' ? ' checked' : '') . '><span>Provider brings power</span></label></div></fieldset>';
            $o .= '<p class="util__note" data-util-note aria-live="polite">' . nu_icon('check-circle-fill') . '<span>Self-contained visit: provider brings water and power.</span></p></div>';
            break;
        case 'vehicle':
        case 'vehicle4':
            $vv = (array) ($val ?? []);
            $parts = $f['type'] === 'vehicle4' ? [['Year', 'Select year', ['2024', '2023', '2022', '2021', '2020', '2019', '2018', 'Older']], ['Make', 'Select make', ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'Hyundai', 'Other']], ['Model', 'Select model', ['RAV4', 'Corolla', 'Camry', 'Other']], ['Trim / engine', 'Select if known', ['LE 2.5 L', 'XLE 2.5 L', 'Hybrid', 'Not sure']]]
                                              : [['Year', '', ['2022', '2021', '2020', '2019', '2018']], ['Make', '', ['Toyota', 'Honda', 'Ford', 'Hyundai']], ['Model', '', ['RAV4', 'Corolla', 'Camry']]];
            $o .= '<fieldset class="fset"><legend class="label">' . (!empty($f['icon']) ? nu_icon($f['icon']) : '') . nu_e($label) . '</legend><div class="vgrid vgrid--' . count($parts) . '">';
            foreach ($parts as $k => $pt) {
                $pid = $id . '-' . $k; $sel = $vv[$k] ?? '';
                $o .= '<label class="vgrid__f" for="' . $pid . '"><span class="label label--sm">' . nu_e($pt[0]) . '</span><select class="input" id="' . $pid . '" name="' . nu_e($name) . '[' . strtolower(strtok($pt[0], ' ')) . ']">';
                if ($pt[1] !== '') $o .= '<option value="">' . nu_e($pt[1]) . '</option>';
                foreach ($pt[2] as $op) { $o .= '<option' . ($sel === $op ? ' selected' : '') . '>' . nu_e($op) . '</option>'; }
                $o .= '</select></label>';
            }
            $o .= '</div></fieldset>';
            break;
        case 'textarea':
            $max = (int) ($f['max'] ?? 500);
            $o .= $lab($id) . '<textarea class="input" id="' . $id . '" name="' . nu_e($name) . '" rows="2" maxlength="' . $max . '" placeholder="' . nu_e($f['placeholder'] ?? '') . '" data-chars></textarea>';
            if (!empty($f['max'])) $o .= '<span class="counter" aria-hidden="true"><span data-count-out>0</span>/' . $max . '</span>';
            break;
        case 'upload':
            $o .= '<span class="label">' . nu_e($label) . '</span><label class="drop2" for="' . $id . '">' . nu_icon('image') . '<span>Drag and drop photos here<small>or click to upload</small></span><span class="btn btn-soft btn-sm">' . nu_icon('upload') . ' Add photos</span>'
                . '<input type="file" id="' . $id . '" name="' . nu_e($name) . '[]" accept="image/*" multiple class="sr-only" data-files></label>';
            break;
        default: // text
            $o .= $lab($id) . '<span class="input-icon">' . ($ic ?: nu_icon('pencil')) . '<input class="input" id="' . $id . '" name="' . nu_e($name) . '" value="' . nu_e(is_string($val) ? $val : '') . '" placeholder="' . nu_e($f['placeholder'] ?? '') . '"></span>';
    }
    return '<div class="fld' . (!empty($f['half']) ? ' fld--half' : '') . '">' . $o . '</div>';
}

/** Render a list of form fields, pairing consecutive half-width fields in one row. */
function nu_form_fields($fields, $idp = 'f') {
    $o = ''; $buf = [];
    foreach ($fields as $f) {
        if (!empty($f['half'])) { $buf[] = nu_form_field($f, $idp); if (count($buf) === 2) { $o .= '<div class="fld-row">' . implode('', $buf) . '</div>'; $buf = []; } continue; }
        if ($buf) { $o .= '<div class="fld-row">' . implode('', $buf) . '</div>'; $buf = []; }
        $o .= nu_form_field($f, $idp);
    }
    if ($buf) $o .= '<div class="fld-row">' . implode('', $buf) . '</div>';
    return $o;
}

/**
 * Dark sidebar for the provider / admin consoles (doc1 p4-5, doc2 p19-20). Becomes a scrolling pill row on mobile.
 * $items = [[key, label, url, icon, badge?], ...]
 */
function nu_console_nav($items, $active, $label, $tagline = '') {
    $o = '<aside class="console__side" aria-label="' . nu_e($label) . '"><a class="console__brand" href="' . nu_e(nu_home_url()) . '">' . nu_logo(52, 'dark') . '</a><nav class="console__nav">';
    foreach ($items as $it) {
        $o .= '<a href="' . nu_e($it[2]) . '"' . ($it[0] === $active ? ' aria-current="page"' : '') . '>' . nu_icon($it[3]) . '<span>' . nu_e($it[1]) . '</span>'
            . (!empty($it[4]) ? '<b class="console__badge">' . (int) $it[4] . '</b>' : '') . '</a>';
    }
    $o .= '</nav>';
    if ($tagline !== '') $o .= '<div class="console__art" aria-hidden="true"></div><p class="console__tag">' . $tagline . '</p>';
    return $o . '</aside>';
}

/** Contractor console nav shared by provider_dashboard.php and open_jobs.php. */
function nu_provider_nav() {
    $m = nu_fixture('marketplace'); $u = function ($p) { return nu_url('public/' . $p); };
    return [
        ['today', 'Today', $u('provider_dashboard.php'), 'calendar-event'],
        ['open', 'Open jobs', $u('open_jobs.php'), 'megaphone', count($m['open_jobs'])],
        ['jobs', 'My jobs', $u('provider_home.php'), 'list-ul'],
        ['route', 'Route', $u('provider_route.php'), 'geo-alt'],
        ['messages', 'Messages', $u('messages.php'), 'chat-square', 2],
        ['earnings', 'Earnings', $u('provider_dashboard.php') . '#earnings', 'wallet2'],
        ['availability', 'Availability', $u('provider_dashboard.php') . '#availability', 'calendar2-week'],
        ['support', 'Support', $u('chat.php') . '?thread=support', 'question-circle'],
    ];
}

/** One "open job" card with Accept and Counter-bid (UI only). $j is a row from fixtures/marketplace.php ['open_jobs']. Behaviour: public/assets/jobs.js */
function nu_open_job_card($j) {
    $pct = (int) (nu_fixture('marketplace')['commission_pct'] ?? 12);
    $roadside = !empty($j['roadside']); $quote = !empty($j['quote']); $budget = (int) $j['budget'];
    $svc = $j['svc'] === 'snow-removal' ? 'snow' : 'wrench-adjustable';
    $o = '<article class="oj" data-oj data-id="' . nu_e($j['id']) . '" data-budget="' . $budget . '" data-pct="' . $pct . '" data-kind="' . ($roadside ? 'roadside' : ($quote ? 'quote' : 'bid')) . '" data-svc="' . nu_e($j['svc']) . '">';
    $o .= '<div class="oj__top"><span class="oj__ic">' . nu_icon($svc) . '</span><div class="oj__t"><h3>' . nu_e($j['title']) . '</h3><p>' . nu_icon('geo-alt') . ' ' . nu_e($j['area']) . ' · ' . nu_e($j['dist']) . '</p></div>';
    $o .= '<div class="oj__chips">' . ($roadside ? '<span class="chip chip--warning">' . nu_icon('broadcast') . 'Roadside · now</span>' : '') . (!empty($j['boost']) ? '<span class="chip chip--blue">' . nu_icon('rocket-takeoff-fill') . 'Boosted</span>' : '') . ($quote ? '<span class="chip chip--plain">Quote needed</span>' : '') . '</div></div>';
    $o .= '<p class="oj__m">' . nu_icon('clock') . ' ' . nu_e($j['when']) . ' <span>· posted ' . nu_e($j['posted']) . '</span><span>· ' . (int) $j['bids'] . ' offer' . ((int) $j['bids'] === 1 ? '' : 's') . ' so far</span></p>';
    $o .= '<p class="oj__s">' . nu_e($j['scope']) . '</p>';
    if ($quote) {
        $o .= '<div class="oj__bud"><span><small>Client budget</small><b>Send a quote</b></span><span class="oj__pay">Itemise parts, labour and call-out fee</span></div>';
        $o .= '<div class="oj__acts"><a class="btn btn-primary btn-sm" href="' . nu_e(nu_url('public/quote.php')) . '?state=draft">' . nu_icon('receipt') . ' Send quote</a><button class="btn btn-ghost btn-sm" type="button" data-oj-skip>Not for me</button></div>';
    } else {
        $o .= '<div class="oj__bud"><span><small>' . ($roadside ? 'Offered price' : 'Client target') . '</small><b>$' . $budget . '</b></span><span class="oj__pay">You receive about <b data-oj-pay>$' . number_format($budget * (100 - $pct) / 100, 2) . '</b> after ' . $pct . '% commission</span></div>';
        $o .= '<div class="oj__acts"><button class="btn btn-primary btn-sm" type="button" data-oj-accept>' . nu_icon('check2') . ' ' . ($roadside ? 'Accept and go' : 'Accept at $' . $budget) . '</button>'
            . ($roadside ? '' : '<button class="btn btn-outline btn-sm" type="button" data-oj-counter aria-expanded="false">' . nu_icon('arrow-left-right') . ' Counter-bid</button>') . '<button class="btn btn-ghost btn-sm" type="button" data-oj-skip>Not for me</button></div>';
        if (!$roadside) {
            $o .= '<form class="oj__cb" data-oj-form hidden><div class="oj__cbrow"><label class="label" for="cb-' . nu_e($j['id']) . '">Your price</label><div class="oj__step"><button type="button" data-oj-step="-5" aria-label="Lower by $5">−</button><span><i>$</i><input class="input" id="cb-' . nu_e($j['id']) . '" type="number" inputmode="numeric" min="10" max="999" value="' . ($budget + 10) . '" data-oj-price></span><button type="button" data-oj-step="5" aria-label="Raise by $5">+</button></div></div>'
                . '<div class="oj__cbrow"><label class="label" for="cn-' . nu_e($j['id']) . '">Arrival</label><select class="input" id="cn-' . nu_e($j['id']) . '"><option>Within 1 hour</option><option>Within 2 hours</option><option>Tomorrow morning</option></select></div>'
                . '<div class="oj__cbrow"><label class="label" for="cm-' . nu_e($j['id']) . '">Note to the client</label><input class="input" id="cm-' . nu_e($j['id']) . '" maxlength="120" placeholder="e.g. Ice melt included, equipment on site"></div>'
                . '<p class="oj__pay">You receive about <b data-oj-cpay>$0.00</b> after ' . $pct . '% commission · <span data-oj-vs></span></p>'
                . '<div class="oj__acts"><button class="btn btn-primary btn-sm" type="submit">Send offer</button><button class="btn btn-ghost btn-sm" type="button" data-oj-cancel>Cancel</button></div></form>';
        }
    }
    $o .= '<p class="oj__res" data-oj-res role="status" hidden></p></article>';
    return $o;
}

/** Vehicle selector: Year, Make, Model, Trim, Engine. Model options depend on the make (data-vehicle JSON; behaviour in public/assets/mech.js). SAMPLE lists from fixtures/mechanic.php. */
function nu_vehicle_select($name = 'vehicle') {
    $m = nu_fixture('mechanic'); $n = nu_e($name);
    $years = array_merge(range(2026, 2005), ['Older']);
    $sel = function ($key, $label, $ph, $opts, $dis = false) use ($n) {
        $o = '<label class="vgrid__f" for="' . $n . '-' . $key . '"><span class="label label--sm">' . nu_e($label) . '</span><select class="input" id="' . $n . '-' . $key . '" name="' . $n . '[' . $key . ']" data-v="' . $key . '"' . ($dis ? ' disabled' : '') . '><option value="">' . nu_e($ph) . '</option>';
        foreach ($opts as $x) $o .= '<option>' . nu_e($x) . '</option>';
        return $o . '</select></label>';
    };
    return '<fieldset class="fset" data-vehicle=\'' . nu_e(json_encode($m['vehicles'])) . '\'><legend class="label">Your vehicle</legend><div class="vgrid vgrid--5">'
        . $sel('year', 'Year', 'Select', $years) . $sel('make', 'Make', 'Select', array_keys($m['vehicles'])) . $sel('model', 'Model', 'Select make first', [], true)
        . $sel('trim', 'Trim', 'If known', $m['trims']) . $sel('engine', 'Engine', 'If known', $m['engines'])
        . '</div></fieldset>';
}

/** Operations console nav shared by operations.php and service_settings.php. */
function nu_ops_nav() {
    $m = nu_fixture('marketplace'); $u = function ($p) { return nu_url('public/' . $p); };
    return [
        ['overview', 'Overview', $u('operations.php'), 'grid-fill'],
        ['open', 'Open jobs', $u('operations.php') . '?view=open', 'megaphone', count($m['open_jobs'])],
        ['vetting', 'Vetting queue', $u('operations.php') . '?view=vetting', 'person-vcard', count($m['vetting'])],
        ['disputes', 'Disputes', $u('operations.php') . '?view=disputes', 'flag-fill', count($m['disputes'])],
        ['bookings', 'All jobs', $u('jobs.php'), 'calendar3'],
        ['payments', 'Payments', $u('operations.php') . '#payments', 'credit-card'],
        ['weather', 'Weather', $u('operations.php') . '#weather', 'cloud'],
        ['settings', 'Service settings', $u('service_settings.php'), 'gear-fill'],
        ['support', 'Support', $u('chat.php') . '?thread=support', 'question-circle'],
    ];
}

/** Unread message count for the nav dot (sample data until messages exist). */
function nu_unread_count() {
    if (!in_array(nu_role(), ['client', 'provider'], true)) return 0;
    $n = 0;
    foreach ((nu_fixture('messages')['threads'] ?? []) as $t) { $n += (int) ($t['unread'] ?? 0); }
    return $n;
}

} // end guard
