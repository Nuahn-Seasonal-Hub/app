<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/ops.php: operations back office. Contractor vetting (persisted approve / reject), dispute
// resolution (release / refund / split), open-jobs and audit views, payouts. Every function requires a staff
// role (superadmin or manager) on the server; the page only decides what to show.

require_once __DIR__ . '/quotes.php';

function mk_require_staff() {
    if (!mk_is_staff()) throw new MkError('Operations access is for Nuahn staff.', 403);
}

// ---------- contractor profile (provider side) ----------
function mk_profile_save($uid, array $in) {
    mk_profile_ensure($uid);
    $trades = array_values(array_intersect(array_map('trim', (array) ($in['trades'] ?? [])), ['snow-removal', 'mobile-mechanics']));
    if (!$trades) throw new MkError('Choose at least one service you offer.');
    $addr = mk_clean($in['base_address'] ?? '', 255);
    if (mb_strlen($addr) < 5) throw new MkError('Enter your base address (where you start your jobs from).');
    $radius = max(1, min(100, (int) ($in['radius_km'] ?? MK_DEFAULT_RADIUS_KM)));
    mk_db()->prepare('UPDATE contractor_profiles SET trades = ?, headline = ?, about = ?, equipment = ?, base_address = ?, base_lat = ?, base_lng = ?, radius_km = ? WHERE user_id = ?')
        ->execute([implode(',', $trades), mk_clean($in['headline'] ?? '', 160), mk_clean($in['about'] ?? '', 1500), mk_clean($in['equipment'] ?? '', 255), $addr,
                   mk_coord($in['lat'] ?? null, -90, 90), mk_coord($in['lng'] ?? null, -180, 180), $radius, (int) $uid]);
    mk_audit('profile.saved', 'user', $uid, implode(',', $trades), (int) $uid, 'provider');
}
function mk_profile_submit($uid) {
    $p = mk_profile_ensure($uid);
    if (!in_array($p['vetting_status'], ['draft', 'changes_requested', 'rejected'], true)) throw new MkError('Your application is already with Nuahn.', 409);
    if (!mk_trades($p) || $p['base_address'] === '') throw new MkError('Save your services and base address first.');
    foreach (['chk_identity' => 'ID', 'chk_insurance' => 'insurance'] as $col => $label) {
        if (in_array($p[$col], ['missing', 'rejected'], true)) throw new MkError('Upload your ' . $label . ' document before submitting.');
    }
    if (in_array('mobile-mechanics', mk_trades($p), true) && $p['chk_licence'] !== 'ok' && $p['chk_licence'] !== 'review') throw new MkError('Mobile mechanics must upload a trade licence or certificate.');
    mk_db()->prepare("UPDATE contractor_profiles SET vetting_status = 'pending', submitted_at = NOW() WHERE user_id = ?")->execute([(int) $uid]);
    mk_audit('vetting.submitted', 'user', $uid, '', (int) $uid, 'provider');
    mk_notify_staff(null, 'vetting', 'New contractor application', 'A contractor is waiting in the vetting queue.', nu_url('public/operations.php') . '?view=vetting');
}
function mk_profile_online($uid, $online) {
    mk_db()->prepare('UPDATE contractor_profiles SET is_online = ? WHERE user_id = ?')->execute([$online ? 1 : 0, (int) $uid]);
}
function mk_documents($uid) {
    $st = mk_db()->prepare('SELECT * FROM contractor_documents WHERE user_id = ? ORDER BY id DESC'); $st->execute([(int) $uid]);
    return $st->fetchAll();
}

// ---------- vetting (staff) ----------
function mk_vetting_queue() {
    mk_require_staff();
    return mk_db()->query("SELECT p.*, u.name, u.email, TIMESTAMPDIFF(HOUR, p.submitted_at, NOW()) AS waiting_h FROM contractor_profiles p JOIN users u ON u.id = p.user_id
        WHERE p.vetting_status IN ('pending','in_review','changes_requested') ORDER BY p.submitted_at IS NULL, p.submitted_at")->fetchAll();
}
function mk_vetting_decide($uid, $decision, array $checks, $note) {
    mk_require_staff();
    $p = mk_profile($uid);
    if (!$p) throw new MkError('That applicant was not found.', 404);
    $set = [];
    foreach (['chk_identity', 'chk_insurance', 'chk_licence', 'chk_cert'] as $c) {
        if (isset($checks[$c]) && in_array($checks[$c], ['missing', 'review', 'ok', 'rejected', 'na'], true)) $set[$c] = $checks[$c]; else $set[$c] = $p[$c];
    }
    $note = mk_clean($note, 1000);
    if ($decision === 'approve') {
        foreach ($set as $c => $v) if (!in_array($v, ['ok', 'na'], true)) throw new MkError('Every check must be OK or N/A before approving. Mark them first.', 409);
        $status = 'approved';
    } elseif ($decision === 'reject') { $status = 'rejected'; if ($note === '') throw new MkError('Add a reason so the applicant knows why.'); }
    elseif ($decision === 'changes') { $status = 'changes_requested'; if ($note === '') throw new MkError('Say what needs to change.'); }
    elseif ($decision === 'review') { $status = 'in_review'; }
    else throw new MkError('Unknown decision.');
    mk_db()->prepare('UPDATE contractor_profiles SET vetting_status = ?, chk_identity = ?, chk_insurance = ?, chk_licence = ?, chk_cert = ?, vet_note = ?, vetted_by = ?, vetted_at = NOW() WHERE user_id = ?')
        ->execute([$status, $set['chk_identity'], $set['chk_insurance'], $set['chk_licence'], $set['chk_cert'], $note, mk_uid(), (int) $uid]);
    mk_audit('vetting.' . $status, 'user', $uid, $note);
    $msg = ['approved' => ['You are approved', 'You can now send offers and quotes on Nuahn jobs.'], 'rejected' => ['Application not approved', $note], 'changes_requested' => ['Changes needed on your application', $note], 'in_review' => ['Your application is in review', 'A reviewer is checking your documents.']][$status];
    mk_notify($uid, null, 'vetting', $msg[0], $msg[1], nu_url('public/contractor_setup.php'));
}

// ---------- disputes (staff) ----------
function mk_disputes_list($status = 'open') {
    mk_require_staff();
    $sql = "SELECT d.*, t.title, t.service, t.mode, t.client_id, t.contractor_id, t.status AS task_status, uc.name AS client_name, uk.name AS contractor_name,
              p.total AS pay_total, p.subtotal AS pay_subtotal, p.state AS pay_state, TIMESTAMPDIFF(HOUR, d.created_at, NOW()) AS age_h
            FROM disputes d JOIN tasks t ON t.id = d.task_id JOIN users uc ON uc.id = t.client_id LEFT JOIN users uk ON uk.id = t.contractor_id LEFT JOIN payments p ON p.task_id = t.id";
    if ($status === 'open') $sql .= " WHERE d.status <> 'resolved'"; elseif ($status === 'resolved') $sql .= " WHERE d.status = 'resolved'";
    return mk_db()->query($sql . ' ORDER BY d.id DESC LIMIT 100')->fetchAll();
}
/**
 * Resolve a dispute. release = pay the contractor in full; refund = void the hold, client pays nothing;
 * split = contractor gets $share of the pre-tax price (commission on that share), the rest goes back to the client.
 */
function mk_dispute_resolve($disputeId, $resolution, $share, $note) {
    mk_require_staff();
    $st = mk_db()->prepare('SELECT * FROM disputes WHERE id = ?'); $st->execute([(int) $disputeId]);
    $d = $st->fetch();
    if (!$d) throw new MkError('That dispute was not found.', 404);
    if ($d['status'] === 'resolved') throw new MkError('That dispute is already resolved.', 409);
    $t = mk_task($d['task_id']);
    $pay = mk_payment($d['task_id']);
    if (!$t || $t['status'] !== 'disputed') throw new MkError('The job is not in dispute.', 409);
    if (!$pay || !in_array($pay['state'], ['disputed', 'authorized'], true)) throw new MkError('No held funds to settle on this job.', 409);
    $note = mk_clean($note, 500);
    if ($note === '') throw new MkError('Add a short note explaining the decision.');
    $shareAmt = null;
    if ($resolution === 'release') {
        mk_payment_capture($t['id'], null, 'dispute: release');
        mk_transition($t['id'], ['disputed'], 'approved', ['approved_at' => '@now'], 'dispute released to contractor');
    } elseif ($resolution === 'refund') {
        mk_payment_void($t['id'], 'dispute: refund');
        mk_transition($t['id'], ['disputed'], 'cancelled', ['cancelled_at' => '@now', 'cancel_reason' => 'Dispute resolved: refunded'], 'dispute refunded to client');
    } elseif ($resolution === 'split') {
        $shareAmt = mk_money($share);
        if ($shareAmt <= 0 || $shareAmt >= (float) $pay['subtotal']) throw new MkError('For a split, the contractor share must be above $0 and below $' . number_format((float) $pay['subtotal'], 2) . '.');
        mk_payment_capture($t['id'], $shareAmt, 'dispute: split');
        mk_transition($t['id'], ['disputed'], 'approved', ['approved_at' => '@now'], 'dispute split');
    } else throw new MkError('Choose release, refund or split.');
    mk_db()->prepare("UPDATE disputes SET status = 'resolved', resolution = ?, contractor_share = ?, resolution_note = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?")
        ->execute([$resolution, $shareAmt, $note, mk_uid(), (int) $disputeId]);
    mk_audit('dispute.resolved', 'task', $t['id'], $resolution . ($shareAmt !== null ? ' share ' . number_format($shareAmt, 2) : '') . ': ' . $note);
    if ($t['contractor_id']) mk_profile_refresh_stats($t['contractor_id']);
    $human = ['release' => 'released to the contractor', 'refund' => 'refunded to you in full', 'split' => 'split between you and the contractor'][$resolution];
    mk_notify($t['client_id'], $t['id'], 'dispute', 'Your dispute was resolved', 'Funds were ' . $human . '. ' . $note, nu_url('public/track.php') . '?id=' . (int) $t['id']);
    if ($t['contractor_id']) mk_notify($t['contractor_id'], $t['id'], 'dispute', 'Dispute resolved', ucfirst($human) . '. ' . $note, nu_url('public/track.php') . '?id=' . (int) $t['id']);
}

// ---------- lists ----------
function mk_ops_open_jobs() {
    mk_require_staff();
    return mk_db()->query("SELECT t.id, t.service, t.mode, t.title, t.address, t.status, t.target_budget, t.boost, t.created_at, TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) AS age_min,
        (SELECT COUNT(*) FROM task_offers o WHERE o.task_id = t.id AND o.status = 'sent') AS offers, uc.name AS client_name
        FROM tasks t JOIN users uc ON uc.id = t.client_id WHERE t.status IN ('awaiting_payment','searching','no_provider','quote_pending','open_for_bids') AND NOT (t.mode = 'seasonal' AND t.parent_id IS NOT NULL)
        ORDER BY t.id DESC LIMIT 100")->fetchAll();
}
function mk_ops_jobs($status = '') {
    mk_require_staff();
    $sql = "SELECT t.*, uc.name AS client_name, uk.name AS contractor_name, p.state AS pay_state, p.total AS pay_total FROM tasks t JOIN users uc ON uc.id = t.client_id LEFT JOIN users uk ON uk.id = t.contractor_id LEFT JOIN payments p ON p.task_id = t.id";
    $args = [];
    if ($status !== '') { $sql .= ' WHERE t.status = ?'; $args[] = $status; }
    $st = mk_db()->prepare($sql . ' ORDER BY t.id DESC LIMIT 150'); $st->execute($args);
    return $st->fetchAll();
}
function mk_ops_audit($limit = 100, $q = '') {
    mk_require_staff();
    $limit = max(1, min(500, (int) $limit));
    $sql = 'SELECT a.*, u.name AS actor_name, TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_s FROM mk_audit_log a LEFT JOIN users u ON u.id = a.actor_id';
    $args = [];
    if ($q !== '') { $sql .= ' WHERE a.action LIKE ? OR a.detail LIKE ? OR (a.entity = \'task\' AND a.entity_id = ?)'; $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%'; $args = [$like, $like, (int) preg_replace('/\D/', '', $q)]; }
    $st = mk_db()->prepare($sql . " ORDER BY a.id DESC LIMIT $limit"); $st->execute($args);
    return $st->fetchAll();
}
function mk_ops_payouts() {
    mk_require_staff();
    return mk_db()->query("SELECT p.*, t.title, uk.name AS contractor_name FROM payments p JOIN tasks t ON t.id = p.task_id LEFT JOIN users uk ON uk.id = p.contractor_id WHERE p.payout_status = 'pending' ORDER BY p.id DESC LIMIT 100")->fetchAll();
}
/** Live counts for the operations nav badges. */
function mk_ops_counts() {
    if (!mk_ready()) return ['open' => 0, 'vetting' => 0, 'disputes' => 0, 'payouts' => 0];
    $db = mk_db();
    return [
        'open' => (int) $db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('awaiting_payment','searching','no_provider','quote_pending','open_for_bids') AND NOT (mode = 'seasonal' AND parent_id IS NOT NULL)")->fetchColumn(),
        'vetting' => (int) $db->query("SELECT COUNT(*) FROM contractor_profiles WHERE vetting_status IN ('pending','in_review','changes_requested')")->fetchColumn(),
        'disputes' => (int) $db->query("SELECT COUNT(*) FROM disputes WHERE status <> 'resolved'")->fetchColumn(),
        'payouts' => (int) $db->query("SELECT COUNT(*) FROM payments WHERE payout_status = 'pending'")->fetchColumn(),
    ];
}
