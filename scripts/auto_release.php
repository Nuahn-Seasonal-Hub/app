<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// scripts/auto_release.php: CLI only. Run every 10-15 minutes from cron:
//   */10 * * * * /usr/bin/php /path/to/app/scripts/auto_release.php >> /path/to/cron.log 2>&1
// Releases jobs the client has not answered for 48 h after submission (capture + payout record), expires roadside
// searches nobody accepted, expires old quotes and clears stale checkouts. Safe to run twice at once.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/marketplace/handlers.php';

if (!mk_ready()) { fwrite(STDERR, "Booking tables are not installed yet.\n"); exit(1); }
$lock = fopen(sys_get_temp_dir() . '/nuahn_auto_release.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { echo "Another run is in progress.\n"; exit(0); }
$out = mk_cron_run();
echo date('c') . ' ' . json_encode($out) . "\n";
exit($out['errors'] > 0 ? 2 : 0);
