<?php
// public/register_client.php — redirects to the unified 3-step client signup wizard in register.php.
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/marketplace/core.php';

$next = mk_safe_next($_GET['next'] ?? '');
$qs = $next !== '' ? '?next=' . rawurlencode($next) : '';
header('Location: ' . nu_url('public/register.php') . $qs, true, 302);
exit;
