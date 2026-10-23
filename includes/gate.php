<?php
// includes/gate.php: public-domain launch gate.
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
//
// Until public launch, the public domain shows only the Coming Soon splash. Every other host (the temporary
// Hostinger address, localhost, Docker) runs the full app. A preview link lets the team see the full app on the
// public domain: ?preview=<secret> on any URL sets a 30-day HttpOnly Secure cookie.
//
// Safe to include more than once and from several places (php.ini auto_prepend_file, config/init.php,
// includes/header.php, and entry scripts that skip both): only the first call does any work.
// Needs no database, no session and no other include, so it runs before any of them.
//
// Secret: env NUAHN_PREVIEW_KEY, or the untracked config/preview.local.php (never committed, see .gitignore).
// With no secret configured the gate stays closed (no bypass is possible).
// Gated hosts: env NUAHN_GATE_HOSTS (comma separated) or the two public hostnames below.
// To open the site at launch: set NUAHN_GATE_HOSTS to "-" (or remove this include), see docs/UI-DATA-HOOKUP.md.

if (defined('NU_GATE_DONE')) { return; }
define('NU_GATE_DONE', true);
if (PHP_SAPI === 'cli') { return; }

(function () {
    $hostsEnv = getenv('NUAHN_GATE_HOSTS');
    $gated = ($hostsEnv === false || $hostsEnv === '') ? ['nuahnseasonalhub.com', 'www.nuahnseasonalhub.com'] : array_filter(array_map('trim', explode(',', strtolower($hostsEnv))), function ($h) { return $h !== '' && $h !== '-'; });
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = rtrim(preg_replace('/:\d+$/', '', $host), '.');
    $isPublic = in_array($host, ['nuahnseasonalhub.com', 'www.nuahnseasonalhub.com'], true);
    if (!defined('NU_PUBLIC_HOST')) { define('NU_PUBLIC_HOST', $isPublic); }

    // Every host other than the public domain (temp address, staging) stays out of search engines.
    if ($host !== '' && !$isPublic) { header('X-Robots-Tag: noindex, nofollow'); }

    if (!in_array($host, $gated, true)) { return; }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

    // Preview secret (never logged or echoed).
    $key = getenv('NUAHN_PREVIEW_KEY');
    if (($key === false || $key === '') && is_file(__DIR__ . '/../config/preview.local.php')) {
        $cfg = include __DIR__ . '/../config/preview.local.php';
        $key = is_array($cfg) ? ($cfg['key'] ?? '') : (string) $cfg;
    }
    $key = is_string($key) ? trim($key) : '';
    $token = $key !== '' ? hash_hmac('sha256', 'nuahn-preview-v1', $key) : '';
    $cookie = 'nu_preview';

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $path = $path === '' ? '/' : $path;

    // 1) ?preview=<secret>: set the cookie, then reload the same URL without the secret.
    if ($key !== '' && isset($_GET['preview']) && is_string($_GET['preview']) && hash_equals($key, $_GET['preview'])) {
        $q = $_GET;
        unset($q['preview']);
        $to = '/' . ltrim($path, '/');
        $to = preg_replace('#/{2,}#', '/', $to);
        if ($q) { $to .= '?' . http_build_query($q); }
        if (!$https) {
            // The cookie is Secure: only hand it out over HTTPS, so send the visitor to the HTTPS URL first.
            header('Location: https://' . $host . $to . (strpos($to, '?') === false ? '?' : '&') . 'preview=' . rawurlencode($key), true, 302);
            header('Cache-Control: no-store');
            exit;
        }
        setcookie($cookie, $token, ['expires' => time() + 30 * 86400, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $to, true, 302);
        exit;
    }

    // 2) Valid preview cookie: run the full app.
    if ($token !== '' && isset($_COOKIE[$cookie]) && is_string($_COOKIE[$cookie]) && hash_equals($token, $_COOKIE[$cookie])) {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: private, no-cache', true);
        header('Vary: Cookie', false);
        return;
    }

    // 3) The splash page itself (and its notify form post) is always allowed.
    $script = isset($_SERVER['SCRIPT_FILENAME']) ? realpath((string) $_SERVER['SCRIPT_FILENAME']) : false;
    $splash = realpath(__DIR__ . '/../public/coming_soon.php');
    if ($script !== false && $splash !== false && $script === $splash) { return; }

    // 4) Home URL: show the splash right here (HTTP 200).
    if (in_array($path, ['/', '/index.php', '/public', '/public/', '/public/index.php'], true) && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        define('NU_SPLASH_VIA_GATE', true);
        header('Cache-Control: no-cache');
        header('Vary: Cookie', false);
        require __DIR__ . '/../public/coming_soon.php';
        exit;
    }

    // 5) Anything else (pages, actions, APIs): back to the splash.
    header('Cache-Control: no-store');
    header('Location: /', true, ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? 302 : 303);
    exit;
})();
