<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/core.php: shared helpers for the booking backend: CSRF, errors, audit log, notifications,
// geo distance, money and tax maths, task access rules and the job state machine (one guarded transition function).
// Every query here is a PDO prepared statement. See docs/BOOKING-BACKEND.md.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../ui.php'; // nu_url() for notification links

/** Raised for any rejected action. $code is the HTTP-style outcome: 403 forbidden, 404 not found/hidden, 409 wrong state, 422 bad input. */
class MkError extends RuntimeException {
    public function __construct($message, $code = 422) { parent::__construct($message, (int) $code); }
}

function mk_db() { global $pdo; return $pdo; }
function mk_uid() { return (int) ($_SESSION['user_id'] ?? 0); }
function mk_role() { return (string) ($_SESSION['role'] ?? ''); }
function mk_is_staff() { return in_array(mk_role(), ['superadmin', 'manager', 'admin'], true); }
function mk_ip() { return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45); }

/** True once database/migrations.sql has created the booking tables (pages show a clear message otherwise). */
function mk_ready() {
    static $ok = null;
    if ($ok === null) {
        try { mk_db()->query('SELECT 1 FROM tasks LIMIT 1'); $ok = true; } catch (Throwable $e) { $ok = false; }
    }
    return $ok;
}

// ---------- CSRF ----------
function mk_csrf_token() {
    if (empty($_SESSION['mk_csrf'])) { $_SESSION['mk_csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['mk_csrf'];
}
function mk_csrf_field() { return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(mk_csrf_token(), ENT_QUOTES, 'UTF-8') . '">'; }
function mk_csrf_ok() {
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && !empty($_SESSION['mk_csrf']) && hash_equals($_SESSION['mk_csrf'], $sent);
}

// ---------- audit + notifications ----------
function mk_audit($action, $entity = '', $entityId = null, $detail = '', $actorId = null, $actorRole = null) {
    try {
        $st = mk_db()->prepare('INSERT INTO mk_audit_log (actor_id, actor_role, action, entity, entity_id, detail, ip) VALUES (?,?,?,?,?,?,?)');
        $st->execute([$actorId ?? (mk_uid() ?: null), $actorRole ?? (mk_role() ?: 'system'), substr($action, 0, 60), substr($entity, 0, 30), $entityId, substr((string) $detail, 0, 500), mk_ip()]);
    } catch (Throwable $e) { error_log('mk_audit: ' . $e->getMessage()); }
}

/** In-app notification. SMS/email delivery is pending (see docs/BOOKING-BACKEND.md); mk_notify is the single hook to extend. */
function mk_notify($userId, $taskId, $type, $title, $body = '', $link = '') {
    if (!$userId) return;
    try {
        $st = mk_db()->prepare('INSERT INTO job_notifications (user_id, task_id, type, title, body, link) VALUES (?,?,?,?,?,?)');
        $st->execute([(int) $userId, $taskId ?: null, substr($type, 0, 40), substr($title, 0, 160), substr($body, 0, 400), substr($link, 0, 200)]);
    } catch (Throwable $e) { error_log('mk_notify: ' . $e->getMessage()); }
}
function mk_notify_staff($taskId, $type, $title, $body = '', $link = '') {
    $rows = mk_db()->query("SELECT id FROM users WHERE role IN ('superadmin','manager','admin')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $id) mk_notify($id, $taskId, $type, $title, $body, $link);
}
function mk_unread_count($userId = null) {
    $userId = $userId ?? mk_uid();
    if (!$userId || !mk_ready()) return 0;
    $st = mk_db()->prepare('SELECT COUNT(*) FROM job_notifications WHERE user_id = ? AND read_at IS NULL');
    $st->execute([$userId]);
    return (int) $st->fetchColumn();
}

// ---------- maths ----------
function mk_money($v) { return round((float) $v, 2); }
/** [gst, pst] on a pre-tax subtotal (sample rates in config.php). */
function mk_tax($subtotal) { return [mk_money($subtotal * MK_GST_PCT / 100), mk_money($subtotal * MK_PST_PCT / 100)]; }
/** Commission and contractor payout on the PRE-TAX amount. Tax is collected on top and is not part of the split. */
function mk_split($subtotal, $pct = null) {
    $pct = $pct ?? MK_COMMISSION_PCT;
    $c = mk_money($subtotal * $pct / 100);
    return ['commission' => $c, 'payout' => mk_money($subtotal - $c), 'pct' => $pct];
}
function mk_cents($amount) { return (int) round($amount * 100); }

function mk_distance_km($lat1, $lng1, $lat2, $lng2) {
    $r = 6371.0; $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
    $dp = $p2 - $p1; $dl = deg2rad($lng2 - $lng1);
    $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($a)));
}

function mk_ref($task) {
    $p = ['snow-removal' => 'SNOW', 'mobile-mechanics' => (($task['mode'] ?? '') === 'roadside' ? 'ROAD' : 'MECH')];
    return ($p[$task['service']] ?? 'JOB') . '-' . (1000 + (int) $task['id']);
}
/** City-level area only: the exact address is shared with a contractor once they hold the job. */
function mk_area($address) {
    $parts = array_values(array_filter(array_map('trim', explode(',', (string) $address))));
    if (count($parts) >= 3) return implode(', ', array_slice($parts, -2));
    if (count($parts) === 2) return $parts[1];
    return 'Edmonton area';
}
function mk_ago($seconds) {
    $s = max(0, (int) $seconds);
    if ($s < 90) return 'just now';
    if ($s < 3600) return (int) round($s / 60) . ' min ago';
    if ($s < 86400) return (int) round($s / 3600) . ' h ago';
    return (int) round($s / 86400) . ' d ago';
}
function mk_clean($v, $max = 255) {
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $v));
    return mb_substr($v, 0, $max);
}

// ---------- state machine ----------
/** Allowed job transitions. mk_transition() refuses anything not listed (documented in docs/BOOKING-BACKEND.md). */
function mk_state_graph() {
    return [
        'awaiting_payment' => ['searching', 'open_for_bids', 'confirmed', 'cancelled'],
        'searching'        => ['confirmed', 'no_provider', 'cancelled'],
        'no_provider'      => ['searching', 'cancelled'],
        'quote_pending'    => ['confirmed', 'cancelled'],
        'open_for_bids'    => ['confirmed', 'cancelled'],
        'confirmed'        => ['on_the_way', 'open_for_bids', 'quote_pending', 'searching', 'cancelled', 'disputed'],
        'on_the_way'       => ['arrived', 'open_for_bids', 'quote_pending', 'searching', 'disputed'],
        'arrived'          => ['in_progress', 'paused', 'disputed'],
        'in_progress'      => ['pending_approval', 'paused', 'disputed'],
        'paused'           => ['in_progress', 'disputed'],
        'pending_approval' => ['approved', 'disputed'],
        'disputed'         => ['approved', 'cancelled'],
        'approved'         => [],
        'cancelled'        => [],
    ];
}

/**
 * The only place tasks.status changes. Atomic: the UPDATE only matches while the task is still in one of $from,
 * so two parallel requests cannot both win. $set = extra columns ('@now' means NOW()). Returns true if it moved.
 */
function mk_transition($taskId, array $from, $to, array $set = [], $detail = '') {
    $graph = mk_state_graph();
    foreach ($from as $f) {
        if (!in_array($to, $graph[$f] ?? [], true)) { throw new LogicException("Illegal transition $f -> $to"); }
    }
    $cols = ['status = ?']; $args = [$to];
    foreach ($set as $col => $val) {
        if (!preg_match('/^[a-z_]+$/', $col)) continue;
        if ($val === '@now') { $cols[] = "$col = NOW()"; }
        elseif ($val === null) { $cols[] = "$col = NULL"; }
        else { $cols[] = "$col = ?"; $args[] = $val; }
    }
    $args[] = (int) $taskId;
    $in = implode(',', array_fill(0, count($from), '?'));
    foreach ($from as $f) $args[] = $f;
    $st = mk_db()->prepare('UPDATE tasks SET ' . implode(', ', $cols) . " WHERE id = ? AND status IN ($in)");
    $st->execute($args);
    $moved = $st->rowCount() === 1;
    if ($moved) mk_audit('job.' . $to, 'task', $taskId, $detail);
    return $moved;
}

// ---------- tasks and access ----------
const MK_TASK_SELECT = "SELECT t.*, TIMESTAMPDIFF(SECOND, t.created_at, NOW()) AS age_s,
        TIMESTAMPDIFF(MINUTE, NOW(), t.auto_release_at) AS release_in_min,
        TIMESTAMPDIFF(MINUTE, NOW(), t.search_expires_at) AS search_left_min,
        uc.name AS client_name, uk.name AS contractor_name,
        p.provider AS pay_provider, p.state AS pay_state, p.subtotal AS pay_subtotal, p.tax AS pay_tax, p.total AS pay_total,
        p.commission_pct AS pay_pct, p.commission AS pay_commission, p.payout_amount AS pay_payout, p.captured_amount AS pay_captured,
        p.refunded_amount AS pay_refunded, p.payout_status AS pay_payout_status, p.processor_ref AS pay_ref
    FROM tasks t JOIN users uc ON uc.id = t.client_id LEFT JOIN users uk ON uk.id = t.contractor_id LEFT JOIN payments p ON p.task_id = t.id ";

function mk_task($id) {
    $st = mk_db()->prepare(MK_TASK_SELECT . 'WHERE t.id = ?');
    $st->execute([(int) $id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Who may open a task page: its client, its assigned contractor, staff, or a vetted contractor looking at an OPEN
 * job on the board (then the exact address is hidden by the page). Anyone else gets "not found", never "forbidden",
 * so task ids cannot be probed.
 * @return array|null [task, relation] relation: client|contractor|bidder|board|staff
 */
function mk_task_access($id, $uid = null, $role = null) {
    $uid = $uid ?? mk_uid(); $role = $role ?? mk_role();
    $t = mk_task($id);
    if (!$t || !$uid) return null;
    if (in_array($role, ['superadmin', 'manager', 'admin'], true)) return [$t, 'staff'];
    if ($role === 'client') return ((int) $t['client_id'] === $uid) ? [$t, 'client'] : null;
    if ($role === 'provider') {
        if ((int) $t['contractor_id'] === $uid) return [$t, 'contractor'];
        $st = mk_db()->prepare('SELECT 1 FROM task_offers WHERE task_id = ? AND contractor_id = ?');
        $st->execute([(int) $id, $uid]);
        // A bidder keeps access only while the job is still open; once awarded to someone else the address and client details are private.
        if ($st->fetchColumn() && in_array($t['status'], ['open_for_bids', 'quote_pending', 'searching', 'no_provider'], true)) return [$t, 'bidder'];
        if (in_array($t['status'], ['open_for_bids', 'quote_pending', 'searching'], true) && mk_is_vetted($uid, $t['service'])) return [$t, 'board'];
    }
    return null;
}
function mk_require_task($id, array $relations = null) {
    $a = mk_task_access($id);
    if (!$a) throw new MkError('That job was not found.', 404);
    if ($relations !== null && !in_array($a[1], $relations, true)) throw new MkError('You do not have access to do that on this job.', 403);
    return $a;
}

// ---------- contractor profile + vetting ----------
function mk_profile($uid) {
    $st = mk_db()->prepare('SELECT * FROM contractor_profiles WHERE user_id = ?');
    $st->execute([(int) $uid]);
    return $st->fetch() ?: null;
}
function mk_profile_ensure($uid) {
    $p = mk_profile($uid);
    if ($p) return $p;
    mk_db()->prepare("INSERT IGNORE INTO contractor_profiles (user_id, trades, vetting_status) VALUES (?, '', 'draft')")->execute([(int) $uid]);
    return mk_profile($uid);
}
function mk_trades($profile) { return array_values(array_filter(array_map('trim', explode(',', (string) ($profile['trades'] ?? ''))))); }
/** Only an APPROVED contractor, with the trade, may offer, quote, or accept. Enforced in every offer/quote/accept function. */
function mk_is_vetted($uid, $service = null) {
    $p = mk_profile($uid);
    if (!$p || $p['vetting_status'] !== 'approved') return false;
    return $service === null || in_array($service, mk_trades($p), true);
}
function mk_require_vetted($uid, $service) {
    if (!mk_is_vetted($uid, $service)) {
        throw new MkError('Your contractor profile must be approved for this service before you can respond to jobs. Finish your application first.', 403);
    }
}
/** Tone class + initials for an avatar, stable per user id. */
function mk_tone($uid) { return ['a', 'b', 'c', 'd', 'e'][((int) $uid) % 5]; }
function mk_initials($name) {
    $w = preg_split('/\s+/', trim((string) $name));
    return strtoupper(mb_substr($w[0] ?? '?', 0, 1) . (count($w) > 1 ? mb_substr(end($w), 0, 1) : ''));
}
/** Contractor card in the shape nu_contractor() returns for fixture contractors, built from live rows. */
function mk_contractor_card($uid) {
    $st = mk_db()->prepare('SELECT u.id, u.name, p.* FROM users u LEFT JOIN contractor_profiles p ON p.user_id = u.id WHERE u.id = ?');
    $st->execute([(int) $uid]);
    $r = $st->fetch();
    if (!$r) return null;
    $trades = mk_trades($r);
    $badges = [];
    if (($r['vetting_status'] ?? '') === 'approved') $badges[] = 'verified';
    if (($r['chk_insurance'] ?? '') === 'ok') $badges[] = 'insured';
    return [
        'id' => (string) $r['id'], 'name' => $r['name'], 'initials' => mk_initials($r['name']), 'tone' => mk_tone($r['id']),
        'trades' => $trades, 'headline' => (string) ($r['headline'] ?? ''), 'about' => (string) ($r['about'] ?? ''), 'equipment' => (string) ($r['equipment'] ?? ''),
        'rating' => (float) ($r['rating_avg'] ?? 0), 'reviews' => (int) ($r['rating_count'] ?? 0), 'jobs' => (int) ($r['jobs_done'] ?? 0),
        'badges' => $badges, 'vetting' => $r['vetting_status'] ?? 'draft', 'area' => mk_area($r['base_address'] ?? ''), 'online' => !empty($r['is_online']),
        'radius' => (int) ($r['radius_km'] ?? 0), 'lat' => $r['base_lat'] ?? null, 'lng' => $r['base_lng'] ?? null,
    ];
}
/** Recompute rating_avg, rating_count and jobs_done after a review or an approval. */
function mk_profile_refresh_stats($uid) {
    $db = mk_db();
    $st = $db->prepare('SELECT COUNT(*), COALESCE(AVG(rating),0) FROM reviews WHERE contractor_id = ?'); $st->execute([(int) $uid]);
    [$n, $avg] = $st->fetch(PDO::FETCH_NUM);
    $st = $db->prepare("SELECT COUNT(*) FROM tasks WHERE contractor_id = ? AND status = 'approved'"); $st->execute([(int) $uid]);
    $jobs = (int) $st->fetchColumn();
    $db->prepare('UPDATE contractor_profiles SET rating_count = ?, rating_avg = ?, jobs_done = ? WHERE user_id = ?')->execute([(int) $n, round((float) $avg, 2), $jobs, (int) $uid]);
}

// ---------- messages ----------
function mk_message_send($taskId, $senderId, $body) {
    $body = mk_clean($body, 1000);
    if ($body === '') throw new MkError('Write a message first.');
    mk_db()->prepare('INSERT INTO job_messages (task_id, sender_id, body) VALUES (?,?,?)')->execute([(int) $taskId, (int) $senderId, $body]);
}
function mk_messages($taskId) {
    $st = mk_db()->prepare('SELECT m.*, u.name, u.role, TIMESTAMPDIFF(SECOND, m.created_at, NOW()) AS age_s FROM job_messages m JOIN users u ON u.id = m.sender_id WHERE m.task_id = ? ORDER BY m.id');
    $st->execute([(int) $taskId]);
    return $st->fetchAll();
}

/** Same-site relative path check for ?next= and back links (blocks open redirects). */
function mk_safe_next($next) {
    $next = (string) $next;
    if ($next === '' || strlen($next) > 300) return '';
    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|\\\\|/\\\\)#i', $next) || preg_match('/[\r\n\x00-\x1f]/', $next)) return '';
    if (!preg_match('#^[A-Za-z0-9_./?=&%+,:;~\-\#\[\]]+$#', $next)) return '';
    $path = ltrim((string) parse_url($next, PHP_URL_PATH), '/');
    if (strpos($path, '..') !== false) return '';
    if (!preg_match('#^(public/)?[A-Za-z0-9_\-]+\.php$#', $path)) return '';
    return $next;
}
