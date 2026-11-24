<?php
// public/login_client.php — redirects to the unified login page (Email + Password & SMS OTP) in login.php.
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/marketplace/core.php';

$next = mk_safe_next($_GET['next'] ?? '');
$qs = $next !== '' ? '?next=' . rawurlencode($next) : '';
header('Location: ' . nu_url('public/login.php') . $qs, true, 302);
exit;
