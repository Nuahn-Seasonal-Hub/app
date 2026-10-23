<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// actions/marketplace.php: the single POST endpoint for the booking backend (snow removal + mobile mechanics).
// Order of checks: method -> signed in -> CSRF token -> action exists -> role allowed -> (handler) ownership/state.
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/marketplace/handlers.php';

function mk_deny($code, $title, $message) {
    http_response_code($code);
    require __DIR__ . '/../includes/header.php';
    echo '<main class="container py-5"><div class="nu-card nu-empty"><h1 class="h4">' . nu_e($title) . '</h1><p class="text-muted">' . nu_e($message) . '</p>'
       . '<a class="btn btn-primary" href="' . nu_e(nu_url('public/index.php')) . '">Back to home</a></div></main>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
function mk_go($url) { header('Location: ' . $url, true, 303); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); mk_deny(405, 'Not available', 'This address only accepts form submissions.'); }
if (!isset($_SESSION['user_id'])) {
    flashError('Please sign in to continue.');
    mk_go(nu_url('public/login.php'));
}
if (!mk_csrf_ok()) mk_deny(403, 'Session expired', 'Your form expired or could not be verified. Go back, refresh the page and try again.');
if (!mk_ready()) mk_deny(503, 'Setting up', 'Bookings are being set up. Please try again in a few minutes.');

$routes = mk_routes();
$do = (string) ($_POST['do'] ?? '');
if (!isset($routes[$do])) mk_deny(404, 'Not found', 'That action does not exist.');
[$roles, $fn] = $routes[$do];
$role = mk_role();
$allowed = false;
foreach ($roles as $r) { if ($r === 'staff' ? mk_is_staff() : $r === $role) { $allowed = true; break; } }
if (!$allowed) mk_deny(403, 'Not allowed', 'Your account type cannot do that.');

$back = mk_safe_next($_POST['back'] ?? '');
$backUrl = $back !== '' ? nu_url(strpos($back, 'public/') === 0 ? $back : 'public/' . $back) : nu_url('public/index.php');

try {
    [$to, $msg] = $fn();
    if ($msg) flashSuccess($msg);
    mk_go($to);
} catch (MkError $e) {
    if (in_array($e->getCode(), [403, 404], true)) mk_deny($e->getCode(), $e->getCode() === 404 ? 'Not found' : 'Not allowed', $e->getMessage());
    flashError($e->getMessage());
    mk_go($backUrl);
} catch (Throwable $e) {
    error_log('marketplace ' . $do . ': ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine());
    flashError('Something went wrong on our side. Please try again.');
    mk_go($backUrl);
}
