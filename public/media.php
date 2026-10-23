<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/media.php: permission-checked file server. Job photos (?p=ID) go to the client, the assigned contractor,
// bidders (vehicle "issue" photos only) and staff. Vetting documents (?d=ID) go to their owner and staff.
// Uploads live in uploads/jobs/ which the web server denies directly; nothing is reachable except through here.
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../includes/marketplace/bootstrap.php';

function mk_media_404() { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found'); }
function mk_media_send($abs, $mime) {
    if (!is_file($abs) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'application/pdf'], true)) mk_media_404();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($abs));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: inline');
    readfile($abs);
    exit;
}
if (!isset($_SESSION['user_id']) || !mk_ready()) mk_media_404();
$uid = mk_uid();

if (isset($_GET['d'])) {
    $st = mk_db()->prepare('SELECT * FROM contractor_documents WHERE id = ?'); $st->execute([(int) $_GET['d']]);
    $d = $st->fetch();
    if (!$d || ((int) $d['user_id'] !== $uid && !mk_is_staff())) mk_media_404();
    if (!preg_match('#^_docs/\d+/[a-f0-9]{32}\.(jpg|pdf)$#', $d['path'])) mk_media_404();
    mk_media_send(MK_UPLOAD_DIR . '/' . $d['path'], $d['mime']);
}

$st = mk_db()->prepare('SELECT * FROM job_photos WHERE id = ?'); $st->execute([(int) ($_GET['p'] ?? 0)]);
$p = $st->fetch();
if (!$p) mk_media_404();
$a = mk_task_access($p['task_id']);
if (!$a) mk_media_404();
if ($a[1] === 'board' || $a[1] === 'bidder') { if ($p['kind'] !== 'issue') mk_media_404(); }
if ($p['storage'] === 'asset') {
    if (!preg_match('#^[a-z0-9\-]+\.(jpg|webp|avif)$#', $p['path'])) mk_media_404();
    $ext = substr($p['path'], strrpos($p['path'], '.') + 1);
    mk_media_send(__DIR__ . '/assets/img/photos/' . $p['path'], ['jpg' => 'image/jpeg', 'webp' => 'image/webp', 'avif' => 'image/avif'][$ext]);
}
if (!preg_match('#^\d+/[a-f0-9]{32}\.jpg$#', $p['path'])) mk_media_404();
mk_media_send(MK_UPLOAD_DIR . '/' . $p['path'], 'image/jpeg');
