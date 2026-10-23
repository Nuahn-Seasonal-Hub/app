<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/jobs.php: task creation, offers, acceptance with escrow, the job lifecycle (with the
// server-side photo gate), cancellation, disputes, reviews, seasonal snow visits and the cron sweep.
// All queries are PDO prepared statements. Every function re-checks ownership, role and state: pages and the action
// router are convenience only, these rules are the authority.

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/photos.php';

function mk_is_plan($t) { return $t['mode'] === 'seasonal' && empty($t['parent_id']); }
function mk_details($t) { $d = json_decode((string) ($t['details'] ?? ''), true); return is_array($d) ? $d : []; }

function mk_coord($v, $min, $max) {
    if ($v === null || $v === '' || !is_numeric($v)) return null;
    $v = (float) $v;
    return ($v >= $min && $v <= $max) ? round($v, 6) : null;
}
function mk_job_link($t, $relation = 'client') {
    $id = (int) $t['id'];
    if ($relation === 'client') {
        if ($t['status'] === 'open_for_bids') return nu_url('public/offers.php') . '?task=' . $id;
        if ($t['status'] === 'quote_pending') return nu_url('public/quote.php') . '?task=' . $id;
        if ($t['status'] === 'pending_approval') return nu_url('public/approve.php') . '?id=' . $id;
    }
    return nu_url('public/track.php') . '?id=' . $id;
}

// ---------- creation ----------
function mk_task_create_snow($clientId, array $in) {
    $address = mk_clean($in['address'] ?? '', 255);
    if (mb_strlen($address) < 5) throw new MkError('Enter the property address.');
    $size = $in['size'] ?? 'standard'; $sn = mk_snow_pricing();
    if (!isset($sn['sizes'][$size])) throw new MkError('Choose a driveway size.');
    $walk = isset($sn['walk'][$in['walk'] ?? '']) ? $in['walk'] : 'none';
    $plan = ($in['plan'] ?? 'demand') === 'seasonal' ? 'seasonal' : 'demand';
    $d = ['size' => $size, 'sqft' => $size === 'commercial' ? max(400, min(200000, (int) ($in['sqft'] ?? 0))) : null, 'walk' => $walk, 'steps' => !empty($in['steps']), 'ice' => !empty($in['ice']), 'plan' => $plan];
    [$lo, $hi, $mid] = mk_snow_estimate($d);
    $budget = (float) ($in['budget'] ?? 0);
    if ($budget < MK_MIN_PRICE || $budget > MK_MAX_PRICE) throw new MkError('Enter a budget between $' . MK_MIN_PRICE . ' and $' . MK_MAX_PRICE . '.');
    $boost = max(0, min(MK_BOOST_MAX, (float) ($in['boost'] ?? 0)));
    $d['estimate'] = [$lo, $hi];
    $title = 'Snow clearing · ' . $sn['sizes'][$size]['label'] . ($walk !== 'none' ? ' + ' . strtolower($sn['walk'][$walk][0]) : '') . ($plan === 'seasonal' ? ' · seasonal plan' : '');
    $db = mk_db();
    $db->prepare("INSERT INTO tasks (service, mode, category, title, client_id, address, lat, lng, details, notes, target_budget, boost, status, payment_status)
        VALUES ('snow-removal', ?, 'snow', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open_for_bids', 'unpaid')")
       ->execute([$plan === 'seasonal' ? 'seasonal' : 'on_demand', $title, (int) $clientId, $address, mk_coord($in['lat'] ?? null, -90, 90), mk_coord($in['lng'] ?? null, -180, 180),
                  json_encode($d), mk_clean($in['notes'] ?? '', 600), $budget, $boost]);
    $id = (int) $db->lastInsertId();
    mk_audit('job.posted', 'task', $id, 'snow ' . $plan . ' budget ' . number_format($budget + $boost, 2), (int) $clientId, 'client');
    mk_broadcast($id);
    return $id;
}

/** Roadside (broadcast) or scheduled repair (quotes). $in: path roadside|repair, svc, vehicle[], address, lat, lng, extras. */
function mk_task_create_mech($clientId, array $in) {
    $m = nu_fixture('mechanic');
    $svc = (string) ($in['svc'] ?? '');
    $road = $m['roadside'][$svc] ?? null; $maint = $m['maintenance'][$svc] ?? null;
    if (!$road && !$maint) throw new MkError('Choose a service from the menu.');
    $address = mk_clean($in['address'] ?? '', 255);
    if (mb_strlen($address) < 5) throw new MkError($road ? 'Add the address where the vehicle is parked.' : 'Add the service location.');
    $lat = mk_coord($in['lat'] ?? null, -90, 90); $lng = mk_coord($in['lng'] ?? null, -180, 180);
    $v = is_array($in['vehicle'] ?? null) ? $in['vehicle'] : [];
    $veh = []; foreach (['year', 'make', 'model', 'trim', 'engine'] as $k) $veh[$k] = mk_clean($v[$k] ?? '', 40);
    if ($veh['make'] === '' || $veh['model'] === '') throw new MkError('Choose the vehicle make and model.');
    $extras = [];
    foreach ((array) ($in['extras'] ?? []) as $k => $val) {
        if (!preg_match('/^[a-z_]{1,24}$/', (string) $k)) continue;
        if (is_array($val)) { $extras[$k] = array_slice(array_map(fn($x) => mk_clean($x, 120), array_filter($val, 'is_scalar')), 0, 10); }
        elseif (is_scalar($val)) { $extras[$k] = mk_clean($val, 400); }
    }
    $details = ['vehicle' => $veh, 'extras' => $extras];
    $name = $road ? $road['name'] : $maint['name'];
    $title = $name . ' · ' . trim($veh['year'] . ' ' . $veh['make'] . ' ' . $veh['model']);
    $db = mk_db();
    if ($road) {
        $range = mk_roadside_pricing()[$svc] ?? [85, 120];
        $price = (float) round(($range[0] + $range[1]) / 2);
        $db->prepare("INSERT INTO tasks (service, mode, category, title, client_id, address, lat, lng, details, notes, target_budget, status, payment_status)
            VALUES ('mobile-mechanics', 'roadside', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'awaiting_payment', 'unpaid')")
           ->execute([$svc, $title, (int) $clientId, $address, $lat, $lng, json_encode($details), mk_clean($in['notes'] ?? '', 600), $price]);
        $id = (int) $db->lastInsertId();
        mk_audit('job.posted', 'task', $id, 'roadside ' . $svc . ' hold ' . number_format($price, 2), (int) $clientId, 'client');
        // The roadside price is the upfront estimate midpoint: the hold is placed before the broadcast.
        [$g, $p] = mk_tax($price);
        mk_payment_prepare($id, $clientId, null, $price, $g + $p);
        $r = mk_payment_authorize($id, ['test_card' => $in['test_card'] ?? '']);
        if ($r['status'] === 'failed') {
            mk_transition($id, ['awaiting_payment'], 'cancelled', ['cancelled_at' => '@now', 'cancel_reason' => 'Payment was not authorised'], 'payment failed');
            throw new MkError($r['message'] ?? 'The payment could not be authorised.', 409);
        }
        if ($r['status'] === 'authorized') mk_after_authorized($id);
        return ['id' => $id, 'pay' => $r['status'] === 'requires_action'];
    }
    $db->prepare("INSERT INTO tasks (service, mode, category, title, client_id, address, lat, lng, details, notes, status, payment_status, window_label)
        VALUES ('mobile-mechanics', 'scheduled', ?, ?, ?, ?, ?, ?, ?, ?, 'quote_pending', 'unpaid', ?)")
       ->execute([$svc, $title, (int) $clientId, $address, $lat, $lng, json_encode($details), mk_clean($in['notes'] ?? '', 600), mk_clean($extras['window'] ?? '', 80)]);
    $id = (int) $db->lastInsertId();
    mk_audit('job.posted', 'task', $id, 'repair request ' . $svc, (int) $clientId, 'client');
    mk_broadcast($id);
    return ['id' => $id, 'pay' => false];
}

/** Vetted contractors for a task's service, nearest first, within radius. @return array [[user_id, name, km|null, ...]] */
function mk_nearby_contractors(array $t, $limit = 10, $maxKm = null) {
    $st = mk_db()->prepare("SELECT p.*, u.name FROM contractor_profiles p JOIN users u ON u.id = p.user_id WHERE p.vetting_status = 'approved' AND FIND_IN_SET(?, p.trades) > 0 AND p.is_online = 1 AND p.user_id <> ?");
    $st->execute([$t['service'], (int) $t['client_id']]);
    $out = [];
    foreach ($st->fetchAll() as $p) {
        $km = null;
        if ($t['lat'] !== null && $t['lng'] !== null && $p['base_lat'] !== null && $p['base_lng'] !== null) {
            $km = mk_distance_km((float) $t['lat'], (float) $t['lng'], (float) $p['base_lat'], (float) $p['base_lng']);
            $reach = $t['mode'] === 'roadside' ? MK_ROADSIDE_RADIUS_KM : max(1, (int) $p['radius_km'] ?: MK_DEFAULT_RADIUS_KM);
            if ($maxKm !== null) $reach = $maxKm;
            if ($km > $reach) continue;
        }
        $p['km'] = $km; $out[] = $p;
    }
    usort($out, fn($a, $b) => ($a['km'] ?? 9999) <=> ($b['km'] ?? 9999));
    return array_slice($out, 0, $limit);
}
/** Alert the nearest technicians/contractors (in-app notifications; SMS/push is pending). @return int notified */
function mk_broadcast($taskId) {
    $t = mk_task($taskId);
    if (!$t) return 0;
    $near = mk_nearby_contractors($t, 10);
    foreach ($near as $p) {
        $km = $p['km'] !== null ? number_format($p['km'], 1) . ' km away' : 'in your area';
        $title = $t['mode'] === 'roadside' ? 'Roadside request near you' : ($t['service'] === 'snow-removal' ? 'New snow task' : 'New repair quote request');
        mk_notify($p['user_id'], $taskId, 'new_job', $title, $t['title'] . ' · ' . mk_area($t['address']) . ' · ' . $km, nu_url('public/open_jobs.php') . '?task=' . (int) $taskId);
    }
    return count($near);
}

// ---------- the board (contractor view) ----------
/** Open jobs a vetted contractor can respond to, nearest/boosted first. Exact addresses are NOT included. */
function mk_board($uid, $service = null) {
    $p = mk_profile($uid);
    if (!$p || $p['vetting_status'] !== 'approved') return [];
    $trades = mk_trades($p);
    if (!$trades) return [];
    $in = implode(',', array_fill(0, count($trades), '?'));
    $sql = "SELECT t.id, t.service, t.mode, t.category, t.title, t.address, t.lat, t.lng, t.details, t.target_budget, t.boost, t.status, t.window_label, t.notes, t.created_at,
              TIMESTAMPDIFF(SECOND, t.created_at, NOW()) AS age_s, TIMESTAMPDIFF(MINUTE, NOW(), t.search_expires_at) AS search_left_min,
              (SELECT status FROM task_offers o WHERE o.task_id = t.id AND o.contractor_id = ?) AS my_offer
            FROM tasks t WHERE t.status IN ('open_for_bids','quote_pending','searching') AND t.client_id <> ? AND t.service IN ($in)
              AND (t.mode <> 'roadside' OR t.search_expires_at > NOW()) AND NOT (t.mode = 'seasonal' AND t.parent_id IS NOT NULL)";
    $args = array_merge([(int) $uid, (int) $uid], $trades);
    if ($service) { $sql .= ' AND t.service = ?'; $args[] = $service; }
    $st = mk_db()->prepare($sql . ' ORDER BY t.id DESC LIMIT 200');
    $st->execute($args);
    $rows = [];
    foreach ($st->fetchAll() as $t) {
        $km = null;
        if ($t['lat'] !== null && $p['base_lat'] !== null) $km = mk_distance_km((float) $t['lat'], (float) $t['lng'], (float) $p['base_lat'], (float) $p['base_lng']);
        $reach = $t['mode'] === 'roadside' ? MK_ROADSIDE_RADIUS_KM : ((int) $p['radius_km'] ?: MK_DEFAULT_RADIUS_KM);
        if ($km !== null && $km > $reach) continue;
        $t['km'] = $km; $t['area'] = mk_area($t['address']); $t['price'] = (float) $t['target_budget'] + (float) $t['boost']; $t['ref'] = mk_ref($t);
        unset($t['address'], $t['lat'], $t['lng']);
        $rows[] = $t;
    }
    usort($rows, fn($a, $b) => [$a['mode'] === 'roadside' ? 0 : 1, -(float) $a['boost'], $a['km'] ?? 9999] <=> [$b['mode'] === 'roadside' ? 0 : 1, -(float) $b['boost'], $b['km'] ?? 9999]);
    return $rows;
}

// ---------- offers ----------
function mk_offers($taskId) {
    $st = mk_db()->prepare("SELECT o.*, TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS age_s FROM task_offers o WHERE o.task_id = ? AND o.status IN ('sent','accepted') ORDER BY o.amount, o.id");
    $st->execute([(int) $taskId]);
    return $st->fetchAll();
}

/** A contractor accepts the posted price (kind accept) or counter-bids. Vetted only. */
function mk_offer_send($taskId, $contractorId, $amount, $message = '', $etaMinutes = null, $counter = false) {
    $t = mk_task($taskId);
    if (!$t || $t['service'] !== 'snow-removal') throw new MkError('That job was not found.', 404);
    mk_require_vetted($contractorId, $t['service']);
    if ((int) $t['client_id'] === (int) $contractorId) throw new MkError('You cannot offer on your own job.', 403);
    if ($t['status'] !== 'open_for_bids') throw new MkError('This job is no longer open for offers.', 409);
    $posted = (float) $t['target_budget'] + (float) $t['boost'];
    $kind = $counter ? 'counter' : 'accept';
    $amount = $counter ? mk_money($amount) : $posted;
    if ($amount < MK_MIN_PRICE || $amount > MK_MAX_PRICE) throw new MkError('Enter a price between $' . MK_MIN_PRICE . ' and $' . MK_MAX_PRICE . '.');
    if ($counter && abs($amount - $posted) < 0.005) $kind = 'accept';
    $eta = $etaMinutes !== null && $etaMinutes !== '' ? max(5, min(2880, (int) $etaMinutes)) : null;
    mk_db()->prepare("INSERT INTO task_offers (task_id, contractor_id, kind, amount, message, eta_minutes, status) VALUES (?,?,?,?,?,?, 'sent')
        ON DUPLICATE KEY UPDATE kind = VALUES(kind), amount = VALUES(amount), message = VALUES(message), eta_minutes = VALUES(eta_minutes), status = 'sent', updated_at = NOW()")
        ->execute([(int) $taskId, (int) $contractorId, $kind, $amount, mk_clean($message, 500), $eta]);
    $id = (int) mk_db()->query('SELECT id FROM task_offers WHERE task_id = ' . (int) $taskId . ' AND contractor_id = ' . (int) $contractorId)->fetchColumn();
    mk_audit('offer.sent', 'task', $taskId, $kind . ' $' . number_format($amount, 2), (int) $contractorId, 'provider');
    mk_notify($t['client_id'], $taskId, 'offer', 'New offer on your task', 'A verified contractor offered $' . number_format($amount, 2) . ' for ' . $t['title'], nu_url('public/offers.php') . '?task=' . (int) $taskId);
    return $id;
}
function mk_offer_withdraw($taskId, $contractorId) {
    $st = mk_db()->prepare("UPDATE task_offers SET status = 'withdrawn' WHERE task_id = ? AND contractor_id = ? AND status = 'sent'");
    $st->execute([(int) $taskId, (int) $contractorId]);
    if (!$st->rowCount()) throw new MkError('There is no open offer to withdraw.', 409);
    mk_audit('offer.withdrawn', 'task', $taskId, '', (int) $contractorId, 'provider');
}

/**
 * The client accepts an offer (or approves a quote): funds are held in escrow first, THEN the job is confirmed.
 * Only the task's own client may do this; the offering contractor never can. Wrong state or a lost race returns 409.
 * @return array ['status' => 'confirmed'|'pay', 'task_id' => int]  'pay' = Stripe card step still needed (pay.php)
 */
function mk_offer_accept($taskId, $offerId, $clientId, array $opts = []) {
    $db = mk_db();
    $t = mk_task($taskId);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That job was not found.', 404);
    if (!in_array($t['status'], ['open_for_bids', 'quote_pending'], true)) throw new MkError('This job is not waiting for an offer to be accepted.', 409);
    $st = $db->prepare("SELECT * FROM task_offers WHERE id = ? AND task_id = ? AND status = 'sent'");
    $st->execute([(int) $offerId, (int) $taskId]);
    $o = $st->fetch();
    if (!$o) throw new MkError('That offer is no longer available.', 409);
    if ((int) $o['contractor_id'] === (int) $clientId) throw new MkError('You cannot accept your own offer.', 403);
    if (!mk_is_vetted($o['contractor_id'], $t['service'])) throw new MkError('That contractor is not currently approved to take jobs.', 409);
    $sub = (float) $o['amount'];
    if ($o['kind'] === 'quote') {
        $q = mk_db()->prepare("SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS left_s FROM quotes WHERE offer_id = ? AND kind = 'initial' AND status IN ('sent','question') ORDER BY version DESC LIMIT 1");
        $q->execute([(int) $offerId]); $q = $q->fetch();
        if (!$q) throw new MkError('That quote is no longer valid.', 409);
        if ($q['expires_at'] !== null && (int) $q['left_s'] < 0) throw new MkError('That quote has expired. Ask for a fresh one.', 409);
        $sub = (float) $q['subtotal']; $tax = mk_money((float) $q['gst'] + (float) $q['pst']);
        if (!empty($opts['window'])) {
            $wins = array_values(array_filter(array_map('trim', explode('|', (string) $q['windows']))));
            if ($wins && !in_array($opts['window'], $wins, true)) throw new MkError('Choose one of the offered times.');
            $db->prepare('UPDATE quotes SET chosen_window = ? WHERE id = ?')->execute([$opts['window'], $q['id']]);
        }
    } else { [$g, $p] = mk_tax($sub); $tax = mk_money($g + $p); }

    // A seasonal plan holds nothing now; each visit is held when a snowfall triggers it.
    if (mk_is_plan($t)) {
        mk_finalize_acceptance($taskId, $offerId);
        return ['status' => 'confirmed', 'task_id' => (int) $taskId];
    }
    mk_payment_prepare($taskId, $clientId, $o['contractor_id'], $sub, $tax);
    $db->prepare('UPDATE tasks SET pending_offer_id = ? WHERE id = ?')->execute([(int) $offerId, (int) $taskId]);
    $r = mk_payment_authorize($taskId, ['test_card' => $opts['test_card'] ?? '']);
    if ($r['status'] === 'failed') {
        $db->prepare('UPDATE tasks SET pending_offer_id = NULL WHERE id = ?')->execute([(int) $taskId]);
        throw new MkError($r['message'] ?? 'The payment could not be authorised. You were not charged.', 409);
    }
    if ($r['status'] === 'requires_action') return ['status' => 'pay', 'task_id' => (int) $taskId];
    mk_after_authorized($taskId);
    return ['status' => 'confirmed', 'task_id' => (int) $taskId];
}

/** Funds are held (sandbox at once, Stripe via return URL or webhook): move the job on. Safe to call twice. */
function mk_after_authorized($taskId) {
    $t = mk_task($taskId);
    if (!$t) return;
    $pay = mk_payment($taskId);
    if (!$pay || $pay['state'] !== 'authorized') return;
    if ($t['status'] === 'awaiting_payment' && $t['mode'] === 'roadside' && !$t['contractor_id']) {
        mk_db()->prepare("UPDATE tasks SET search_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ? AND status = 'awaiting_payment'")->execute([MK_ROADSIDE_SEARCH_MIN, (int) $taskId]);
        if (mk_transition($taskId, ['awaiting_payment'], 'searching', [], 'funds held, broadcasting')) { mk_broadcast($taskId); }
    } elseif ($t['status'] === 'awaiting_payment' && $t['contractor_id'] && $t['parent_id']) {
        if (mk_transition($taskId, ['awaiting_payment'], 'confirmed', ['accepted_at' => '@now'])) {
            mk_notify($t['contractor_id'], $taskId, 'visit', 'Snow visit confirmed', $t['title'] . ' is funded and ready.', mk_job_link($t, 'contractor'));
        }
    } elseif ($t['pending_offer_id'] && in_array($t['status'], ['open_for_bids', 'quote_pending'], true)) {
        mk_finalize_acceptance($taskId, (int) $t['pending_offer_id']);
    }
}

/** Confirm the job for the accepted offer. Atomic: only one acceptance can win. */
function mk_finalize_acceptance($taskId, $offerId) {
    $db = mk_db();
    $db->beginTransaction();
    try {
        $t = $db->prepare('SELECT * FROM tasks WHERE id = ? FOR UPDATE'); $t->execute([(int) $taskId]); $t = $t->fetch();
        $o = $db->prepare('SELECT * FROM task_offers WHERE id = ? AND task_id = ? FOR UPDATE'); $o->execute([(int) $offerId, (int) $taskId]); $o = $o->fetch();
        if (!$t || !$o || !in_array($t['status'], ['open_for_bids', 'quote_pending'], true) || $o['status'] !== 'sent') { $db->rollBack(); return false; }
        $pay = mk_payment($taskId);
        $sub = $pay && !mk_is_plan($t) ? (float) $pay['subtotal'] : (float) $o['amount'];
        $tax = $pay && !mk_is_plan($t) ? (float) $pay['tax'] : mk_tax($sub)[0] + mk_tax($sub)[1];
        $window = '';
        if ($o['kind'] === 'quote') {
            $w = $db->prepare("SELECT chosen_window FROM quotes WHERE offer_id = ? AND kind = 'initial' ORDER BY version DESC LIMIT 1"); $w->execute([(int) $offerId]);
            $window = (string) $w->fetchColumn();
            $db->prepare("UPDATE quotes SET status = 'approved', decided_at = NOW() WHERE offer_id = ? AND kind = 'initial' AND status IN ('sent','question')")->execute([(int) $offerId]);
        }
        $ok = mk_transition($taskId, [$t['status']], 'confirmed', ['contractor_id' => (int) $o['contractor_id'], 'accepted_offer_id' => (int) $offerId, 'pending_offer_id' => null,
            'agreed_amount' => $sub, 'tax_amount' => mk_money($tax), 'eta_minutes' => $o['eta_minutes'], 'accepted_at' => '@now', 'window_label' => $window !== '' ? $window : $t['window_label']], 'offer ' . (int) $offerId . ' accepted');
        if (!$ok) { $db->rollBack(); return false; }
        $db->prepare("UPDATE task_offers SET status = 'accepted' WHERE id = ?")->execute([(int) $offerId]);
        $db->prepare("UPDATE task_offers SET status = 'declined' WHERE task_id = ? AND id <> ? AND status = 'sent'")->execute([(int) $taskId, (int) $offerId]);
        $db->prepare("UPDATE quotes SET status = 'superseded' WHERE task_id = ? AND offer_id <> ? AND status IN ('sent','question')")->execute([(int) $taskId, (int) $offerId]);
        if ($pay && !mk_is_plan($t)) { $db->prepare('UPDATE payments SET contractor_id = ? WHERE id = ?')->execute([(int) $o['contractor_id'], $pay['id']]); }
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    $t2 = mk_task($taskId);
    mk_notify($o['contractor_id'], $taskId, 'accepted', mk_is_plan($t2) ? 'Seasonal plan accepted' : 'Your offer was accepted', $t2['title'] . (mk_is_plan($t2) ? '. Visits are created after each snowfall.' : '. Funds are held. Open the job for the address and start when ready.'), mk_job_link($t2, 'contractor'));
    $others = $db->prepare("SELECT contractor_id FROM task_offers WHERE task_id = ? AND status = 'declined'"); $others->execute([(int) $taskId]);
    foreach ($others->fetchAll(PDO::FETCH_COLUMN) as $cid) mk_notify($cid, $taskId, 'declined', 'Another offer was chosen', $t2['title'], nu_url('public/open_jobs.php'));
    return true;
}

/** First approved, nearby technician to accept a roadside broadcast wins. Atomic claim: everyone else gets 409. */
function mk_roadside_accept($taskId, $techId, $etaMinutes) {
    $t = mk_task($taskId);
    if (!$t || $t['mode'] !== 'roadside') throw new MkError('That job was not found.', 404);
    mk_require_vetted($techId, 'mobile-mechanics');
    if ((int) $t['client_id'] === (int) $techId) throw new MkError('You cannot accept your own request.', 403);
    $p = mk_profile($techId);
    if ($t['lat'] !== null && $p['base_lat'] !== null) {
        if (mk_distance_km((float) $t['lat'], (float) $t['lng'], (float) $p['base_lat'], (float) $p['base_lng']) > MK_ROADSIDE_RADIUS_KM) throw new MkError('That request is outside your service area.', 403);
    }
    $eta = max(5, min(240, (int) $etaMinutes ?: 20));
    $pay = mk_payment($taskId);
    if (!$pay || $pay['state'] !== 'authorized') throw new MkError('This request is not funded yet.', 409);
    $st = mk_db()->prepare("UPDATE tasks SET status = 'confirmed', contractor_id = ?, agreed_amount = ?, tax_amount = ?, eta_minutes = ?, accepted_at = NOW(), search_expires_at = NULL
        WHERE id = ? AND status = 'searching' AND contractor_id IS NULL AND search_expires_at > NOW()");
    $st->execute([(int) $techId, $pay['subtotal'], $pay['tax'], $eta, (int) $taskId]);
    if ($st->rowCount() !== 1) throw new MkError('Another technician already accepted this request, or it has expired.', 409);
    mk_db()->prepare('UPDATE payments SET contractor_id = ? WHERE id = ?')->execute([(int) $techId, $pay['id']]);
    mk_audit('job.confirmed', 'task', $taskId, 'roadside accepted, ETA ' . $eta . ' min', (int) $techId, 'provider');
    mk_notify($t['client_id'], $taskId, 'accepted', 'A technician accepted', 'ETA about ' . $eta . ' min. Funds are held until you approve the work.', nu_url('public/track.php') . '?id=' . (int) $taskId);
}

// ---------- lifecycle (contractor) ----------
/** Contractor steps: trip (confirmed->on_the_way), arrived, start (needs a BEFORE photo). */
function mk_job_step($taskId, $contractorId, $step) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['contractor_id'] !== (int) $contractorId) throw new MkError('That job was not found.', 404);
    if (mk_is_plan($t)) throw new MkError('Seasonal plans run through their visits.', 409);
    $map = ['trip' => [['confirmed'], 'on_the_way', ['started_at' => '@now']], 'arrived' => [['on_the_way'], 'arrived', ['arrived_at' => '@now']], 'start' => [['arrived'], 'in_progress', []]];
    if (!isset($map[$step])) throw new MkError('Unknown step.');
    [$from, $to, $set] = $map[$step];
    if ($step === 'start' && (mk_photo_counts($taskId)['before'] ?? 0) < 1) throw new MkError('Upload at least one BEFORE photo before starting the work.', 409);
    if (!mk_transition($taskId, $from, $to, $set, 'by contractor')) throw new MkError('That step is not available for the job\'s current status.', 409);
    $msg = ['on_the_way' => 'is on the way', 'arrived' => 'has arrived', 'in_progress' => 'has started the work'][$to];
    mk_notify($t['client_id'], $taskId, 'status', ucfirst(str_replace('_', ' ', $to)), ($t['contractor_name'] ?? 'Your contractor') . ' ' . $msg . '.', nu_url('public/track.php') . '?id=' . (int) $taskId);
}

/** Add a photo. before: allowed once confirmed..in_progress; after: during in_progress/pending_approval(not allowed after)... */
function mk_job_photo_add($taskId, $userId, $kind) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['contractor_id'] !== (int) $userId) throw new MkError('That job was not found.', 404);
    $okStates = $kind === 'before' ? ['confirmed', 'on_the_way', 'arrived', 'in_progress', 'paused'] : ['arrived', 'in_progress', 'paused'];
    if (!in_array($kind, ['before', 'after'], true)) throw new MkError('Choose before or after.');
    if (!in_array($t['status'], $okStates, true)) throw new MkError('Photos can no longer be added at this stage.', 409);
    return mk_photo_store($taskId, $userId, $kind, 'photo');
}

/** Submit for approval. SERVER-SIDE PHOTO GATE: needs at least one before AND one after photo. Starts the 48 h auto-release clock. */
function mk_job_submit($taskId, $contractorId, $note = '', array $checklist = []) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['contractor_id'] !== (int) $contractorId) throw new MkError('That job was not found.', 404);
    if ($t['status'] !== 'in_progress') throw new MkError('Start the work before submitting it.', 409);
    $c = mk_photo_counts($taskId);
    if (($c['before'] ?? 0) < 1 || ($c['after'] ?? 0) < 1) throw new MkError('Upload at least one BEFORE and one AFTER photo before submitting completion.', 409);
    $ok = mk_transition($taskId, ['in_progress'], 'pending_approval', ['submitted_at' => '@now', 'completion_note' => mk_clean($note, 800), 'checklist' => json_encode(array_slice(array_map(fn($x) => mk_clean($x, 80), $checklist), 0, 12))], 'submitted');
    if (!$ok) throw new MkError('That job can no longer be submitted.', 409);
    mk_db()->prepare('UPDATE tasks SET auto_release_at = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE id = ?')->execute([MK_AUTO_RELEASE_HOURS, (int) $taskId]);
    mk_notify($t['client_id'], $taskId, 'review', 'Work finished: please review', 'Check the before and after photos, then approve or report a problem. It is approved automatically after ' . MK_AUTO_RELEASE_HOURS . ' hours.', nu_url('public/approve.php') . '?id=' . (int) $taskId);
}

/** Contractor withdraws before arriving. Snow/repair: hold released and the job reopens. Roadside: hold kept, search restarts. */
function mk_job_cancel_contractor($taskId, $contractorId, $reason = '') {
    $t = mk_task($taskId);
    if (!$t || (int) $t['contractor_id'] !== (int) $contractorId) throw new MkError('That job was not found.', 404);
    if (!in_array($t['status'], ['confirmed', 'on_the_way'], true)) throw new MkError('You can only withdraw before arriving. Message the client or contact support.', 409);
    if ($t['parent_id']) throw new MkError('Visits of a seasonal plan cannot be withdrawn. Contact support.', 409);
    $reason = mk_clean($reason, 200) ?: 'Contractor unavailable';
    $db = mk_db();
    if ($t['mode'] === 'roadside') {
        $to = 'searching';
        $set = ['contractor_id' => null, 'accepted_offer_id' => null, 'agreed_amount' => null, 'started_at' => null, 'eta_minutes' => null];
    } else {
        mk_payment_void($taskId, 'contractor withdrew');
        $to = $t['service'] === 'snow-removal' ? 'open_for_bids' : 'quote_pending';
        $set = ['contractor_id' => null, 'accepted_offer_id' => null, 'agreed_amount' => null, 'tax_amount' => 0, 'started_at' => null, 'eta_minutes' => null, 'accepted_at' => null];
        $db->prepare("UPDATE task_offers SET status = 'withdrawn' WHERE task_id = ? AND contractor_id = ? AND status = 'accepted'")->execute([(int) $taskId, (int) $contractorId]);
        $db->prepare("UPDATE quotes SET status = 'superseded' WHERE task_id = ? AND contractor_id = ? AND status = 'approved'")->execute([(int) $taskId, (int) $contractorId]);
    }
    if (!mk_transition($taskId, [$t['status']], $to, $set, 'contractor withdrew: ' . $reason)) throw new MkError('That job changed. Refresh and try again.', 409);
    if ($t['mode'] === 'roadside') {
        $db->prepare('UPDATE tasks SET search_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?')->execute([MK_ROADSIDE_SEARCH_MIN, (int) $taskId]);
        mk_broadcast($taskId);
    } else { mk_broadcast($taskId); }
    mk_notify($t['client_id'], $taskId, 'withdrawn', 'Your contractor withdrew', $reason . '. Your task is open again' . ($t['mode'] === 'roadside' ? ' and the request was sent to other technicians.' : '. Funds were released.'), mk_job_link(mk_task($taskId), 'client'));
}

// ---------- lifecycle (client) ----------
/**
 * Client approval. Atomic claim of pending_approval -> approved FIRST, then capture the held funds.
 * If the capture fails the job returns to pending_approval and Ops is told, so money and status never disagree.
 * @param string $by client|auto
 */
function mk_job_approve($taskId, $by = 'client', $clientId = null) {
    $t = mk_task($taskId);
    if (!$t || ($by === 'client' && (int) $t['client_id'] !== (int) $clientId)) throw new MkError('That job was not found.', 404);
    if ($t['status'] !== 'pending_approval') throw new MkError('This job is not waiting for approval.', 409);
    if (!mk_transition($taskId, ['pending_approval'], 'approved', ['approved_at' => '@now', 'auto_release_at' => null], $by === 'auto' ? 'auto-released after ' . MK_AUTO_RELEASE_HOURS . ' h' : 'approved by client')) throw new MkError('This job was already approved.', 409);
    try {
        $pay = mk_payment_capture($taskId, null, $by === 'auto' ? 'auto-release' : 'client approval');
    } catch (Throwable $e) {
        mk_db()->prepare("UPDATE tasks SET status = 'pending_approval', approved_at = NULL, auto_release_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ? AND status = 'approved'")->execute([(int) $taskId]);
        mk_audit('job.capture_failed', 'task', $taskId, $e->getMessage());
        mk_notify_staff($taskId, 'capture_failed', 'Capture failed on ' . mk_ref($t), $e->getMessage(), nu_url('public/operations.php') . '?view=audit');
        throw $e instanceof MkError ? $e : new MkError('The payment could not be completed. Please try again shortly.', 409);
    }
    if ($t['contractor_id']) {
        mk_profile_refresh_stats($t['contractor_id']);
        mk_notify($t['contractor_id'], $taskId, 'paid', 'Job approved', $t['title'] . ': payout $' . number_format((float) $pay['payout_amount'], 2) . ' after ' . rtrim(rtrim(number_format((float) $pay['commission_pct'], 2), '0'), '.') . '% commission.', nu_url('public/track.php') . '?id=' . (int) $taskId);
    }
    return $pay;
}

function mk_job_cancel_client($taskId, $clientId, $reason = '') {
    $t = mk_task($taskId);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That job was not found.', 404);
    if (in_array($t['status'], ['on_the_way', 'arrived', 'in_progress', 'paused', 'pending_approval'], true)) throw new MkError('The contractor is already on this job. Report a problem so Nuahn can review it, or message them.', 409);
    if (!in_array($t['status'], ['awaiting_payment', 'searching', 'no_provider', 'quote_pending', 'open_for_bids', 'confirmed'], true)) throw new MkError('This job can no longer be cancelled.', 409);
    $reason = mk_clean($reason, 200) ?: 'Cancelled by client';
    if (!mk_transition($taskId, [$t['status']], 'cancelled', ['cancelled_at' => '@now', 'cancel_reason' => $reason, 'pending_offer_id' => null], 'client cancelled: ' . $reason)) throw new MkError('That job changed. Refresh and try again.', 409);
    mk_payment_void($taskId, 'client cancelled');
    mk_db()->prepare("UPDATE task_offers SET status = 'declined' WHERE task_id = ? AND status IN ('sent','accepted')")->execute([(int) $taskId]);
    mk_db()->prepare("UPDATE quotes SET status = 'declined', decided_at = NOW() WHERE task_id = ? AND status IN ('sent','question')")->execute([(int) $taskId]);
    if ($t['contractor_id']) mk_notify($t['contractor_id'], $taskId, 'cancelled', 'Job cancelled by the client', $t['title'], nu_url('public/provider_dashboard.php'));
}

function mk_task_boost($taskId, $clientId, $boost) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That job was not found.', 404);
    if ($t['status'] !== 'open_for_bids' || $t['service'] !== 'snow-removal') throw new MkError('Boost is only available while a task is open for offers.', 409);
    $b = max(0, min(MK_BOOST_MAX, (float) $boost));
    mk_db()->prepare('UPDATE tasks SET boost = ? WHERE id = ?')->execute([$b, (int) $taskId]);
    mk_audit('job.boost', 'task', $taskId, '$' . number_format($b, 2), (int) $clientId, 'client');
    mk_broadcast($taskId);
}

// ---------- disputes + reviews ----------
function mk_dispute_for($taskId) {
    $st = mk_db()->prepare('SELECT d.*, u.name AS opener FROM disputes d JOIN users u ON u.id = d.opened_by WHERE d.task_id = ? ORDER BY d.id DESC LIMIT 1');
    $st->execute([(int) $taskId]);
    return $st->fetch() ?: null;
}
/** The client reports a problem: the job freezes, funds STAY held (never captured while disputed). */
function mk_dispute_open($taskId, $clientId, $reason, $details, $wanted) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That job was not found.', 404);
    if (!in_array($t['status'], ['on_the_way', 'arrived', 'in_progress', 'paused', 'pending_approval'], true)) throw new MkError('A problem can be reported once the contractor is on the job and before it is approved.', 409);
    $reason = mk_clean($reason, 120); if ($reason === '') throw new MkError('Choose what went wrong.');
    $wanted = in_array($wanted, ['refund', 'partial', 'redo'], true) ? $wanted : 'redo';
    if (!mk_transition($taskId, [$t['status']], 'disputed', ['auto_release_at' => null], 'dispute opened')) throw new MkError('That job changed. Refresh and try again.', 409);
    mk_db()->prepare("INSERT INTO disputes (task_id, opened_by, reason, details, wanted, status) VALUES (?,?,?,?,?, 'open')")->execute([(int) $taskId, (int) $clientId, $reason, mk_clean($details, 1500), $wanted]);
    $disputeId = (int) mk_db()->lastInsertId();
    $pay = mk_payment($taskId);
    if ($pay && $pay['state'] === 'authorized') mk_payment_set($pay, 'disputed');
    mk_audit('dispute.opened', 'task', $taskId, $reason . ' / wants ' . $wanted, (int) $clientId, 'client');
    if ($t['contractor_id']) mk_notify($t['contractor_id'], $taskId, 'dispute', 'A problem was reported', 'Funds stay held while Nuahn reviews. You can add your side by message.', nu_url('public/track.php') . '?id=' . (int) $taskId);
    mk_notify_staff($taskId, 'dispute', 'New dispute: ' . mk_ref($t), $reason, nu_url('public/operations.php') . '?view=disputes');
    return $disputeId;
}

function mk_review_add($taskId, $clientId, $rating, $comment) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That job was not found.', 404);
    if ($t['status'] !== 'approved' || !$t['contractor_id']) throw new MkError('You can review a job after you approve it.', 409);
    $rating = (int) $rating; if ($rating < 1 || $rating > 5) throw new MkError('Choose a rating from 1 to 5.');
    $st = mk_db()->prepare('INSERT IGNORE INTO reviews (task_id, client_id, contractor_id, rating, comment) VALUES (?,?,?,?,?)');
    $st->execute([(int) $taskId, (int) $clientId, (int) $t['contractor_id'], $rating, mk_clean($comment, 1000)]);
    if (!$st->rowCount()) throw new MkError('You already reviewed this job.', 409);
    mk_profile_refresh_stats($t['contractor_id']);
    mk_audit('review.added', 'task', $taskId, $rating . '/5', (int) $clientId, 'client');
    mk_notify($t['contractor_id'], $taskId, 'review', 'New review: ' . $rating . ' stars', mk_clean($comment, 120), nu_url('public/provider_dashboard.php'));
}
function mk_review_for($taskId) {
    $st = mk_db()->prepare('SELECT * FROM reviews WHERE task_id = ?'); $st->execute([(int) $taskId]);
    return $st->fetch() ?: null;
}

// ---------- seasonal snow visits ----------
/**
 * Ops records a snowfall. At or above the trigger, every active seasonal plan gets a visit: a child job for the plan's
 * contractor at the plan's per-visit price, funds held for that visit. Re-running the same event key is a no-op.
 * @return array [created, skipped]
 */
function mk_snow_event($key, $cm, $area, $staffId) {
    $key = mk_clean($key, 60); $cm = (float) $cm;
    if ($key === '') throw new MkError('Give the event a name or date, for example 2026-11-03 AM.');
    if ($cm < MK_SEASONAL_TRIGGER_CM) throw new MkError('Snowfall is below the ' . MK_SEASONAL_TRIGGER_CM . ' cm trigger. No visits are created.');
    $db = mk_db();
    $ins = $db->prepare('INSERT IGNORE INTO snow_events (event_key, snowfall_cm, area, created_by) VALUES (?,?,?,?)');
    $ins->execute([$key, $cm, mk_clean($area, 80), (int) $staffId]);
    if (!$ins->rowCount()) throw new MkError('That snowfall event was already recorded.', 409);
    $evId = (int) $db->lastInsertId();
    $plans = $db->query("SELECT * FROM tasks WHERE service = 'snow-removal' AND mode = 'seasonal' AND parent_id IS NULL AND status = 'confirmed' AND contractor_id IS NOT NULL")->fetchAll();
    $created = 0; $skipped = 0;
    foreach ($plans as $pl) {
        $dup = $db->prepare('SELECT 1 FROM tasks WHERE parent_id = ? AND snow_event_id = ?'); $dup->execute([$pl['id'], $evId]);
        if ($dup->fetchColumn()) { $skipped++; continue; }
        $db->prepare("INSERT INTO tasks (service, mode, category, title, client_id, contractor_id, parent_id, snow_event_id, address, lat, lng, details, notes, target_budget, agreed_amount, tax_amount, status, payment_status, window_label)
            VALUES ('snow-removal','seasonal','snow',?,?,?,?,?,?,?,?,?,?,?,?,?, 'awaiting_payment','unpaid', ?)")
           ->execute(['Seasonal visit · ' . mk_clean($key, 40) . ' · ' . rtrim(rtrim(number_format($cm, 1), '0'), '.') . ' cm', $pl['client_id'], $pl['contractor_id'], $pl['id'], $evId, $pl['address'], $pl['lat'], $pl['lng'], $pl['details'], $pl['notes'], $pl['agreed_amount'], $pl['agreed_amount'], $pl['tax_amount'], 'After snowfall']);
        $vid = (int) $db->lastInsertId();
        mk_audit('job.visit_created', 'task', $vid, 'plan ' . $pl['id'] . ' event ' . $evId, (int) $staffId);
        try {
            mk_payment_prepare($vid, $pl['client_id'], $pl['contractor_id'], $pl['agreed_amount'], $pl['tax_amount']);
            $r = mk_payment_authorize($vid);
            if ($r['status'] === 'authorized') mk_after_authorized($vid);
            elseif ($r['status'] === 'requires_action') mk_notify($pl['client_id'], $vid, 'pay', 'Snowfall: confirm today\'s clear', 'Hold the payment for this visit so your contractor can start.', nu_url('public/pay.php') . '?task=' . $vid);
            else mk_notify($pl['client_id'], $vid, 'pay_failed', 'Payment needed for today\'s visit', 'Your payment could not be held. Update it to confirm the visit.', nu_url('public/track.php') . '?id=' . $vid);
        } catch (Throwable $e) { error_log('visit payment: ' . $e->getMessage()); }
        $created++;
    }
    $db->prepare('UPDATE snow_events SET visits_created = ? WHERE id = ?')->execute([$created, $evId]);
    mk_audit('snow.event', 'snow_event', $evId, $key . ' ' . $cm . ' cm, ' . $created . ' visits', (int) $staffId);
    return [$created, $skipped];
}

// ---------- cron sweep ----------
/** Run by scripts/auto_release.php. Idempotent. @return array counts */
function mk_cron_run() {
    $db = mk_db(); $out = ['released' => 0, 'expired_searches' => 0, 'expired_quotes' => 0, 'stale_checkouts' => 0, 'errors' => 0];
    foreach ($db->query("SELECT id FROM tasks WHERE status = 'pending_approval' AND auto_release_at IS NOT NULL AND auto_release_at <= NOW()")->fetchAll(PDO::FETCH_COLUMN) as $id) {
        try { mk_job_approve($id, 'auto'); $out['released']++; } catch (Throwable $e) { $out['errors']++; error_log("auto-release $id: " . $e->getMessage()); }
    }
    foreach ($db->query("SELECT id, client_id, title FROM tasks WHERE status = 'searching' AND search_expires_at IS NOT NULL AND search_expires_at <= NOW()")->fetchAll() as $t) {
        try {
            if (mk_transition($t['id'], ['searching'], 'no_provider', [], 'no technician accepted in time')) {
                mk_payment_void($t['id'], 'no provider');
                mk_notify($t['client_id'], $t['id'], 'no_provider', 'No technician available', 'Nobody accepted in time. You were not charged. Try again or contact support.', nu_url('public/track.php') . '?id=' . (int) $t['id']);
                $out['expired_searches']++;
            }
        } catch (Throwable $e) { $out['errors']++; error_log('expire search: ' . $e->getMessage()); }
    }
    $q = $db->exec("UPDATE quotes SET status = 'expired' WHERE status IN ('sent','question') AND kind = 'initial' AND expires_at IS NOT NULL AND expires_at <= NOW()");
    $out['expired_quotes'] = (int) $q;
    foreach ($db->query("SELECT id FROM tasks WHERE status = 'awaiting_payment' AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)")->fetchAll(PDO::FETCH_COLUMN) as $id) {
        try {
            if (mk_transition($id, ['awaiting_payment'], 'cancelled', ['cancelled_at' => '@now', 'cancel_reason' => 'Payment step not completed'], 'stale checkout')) { mk_payment_void($id, 'stale checkout'); $out['stale_checkouts']++; }
        } catch (Throwable $e) { $out['errors']++; }
    }
    mk_audit('cron.run', 'system', null, json_encode($out), null, 'system');
    return $out;
}

// ---------- lists for the client / contractor dashboards ----------
/** A client's own tasks. $past = finished (approved/cancelled) vs active. */
function mk_client_tasks($clientId, $past = false, $limit = 100) {
    $ops = $past ? "IN ('approved','cancelled')" : "NOT IN ('approved','cancelled')";
    $st = mk_db()->prepare(MK_TASK_SELECT . "WHERE t.client_id = ? AND t.status $ops ORDER BY t.id DESC LIMIT " . (int) $limit);
    $st->execute([(int) $clientId]);
    $rows = $st->fetchAll();
    $cnt = mk_db()->prepare("SELECT task_id, COUNT(*) FROM task_offers WHERE status = 'sent' AND task_id IN (SELECT id FROM tasks WHERE client_id = ?) GROUP BY task_id");
    $cnt->execute([(int) $clientId]);
    $offers = $cnt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($rows as &$r) { $r['offer_count'] = (int) ($offers[$r['id']] ?? 0); }
    return $rows;
}
/** A contractor's assigned jobs, active first. */
function mk_contractor_tasks($uid, $past = false, $limit = 100) {
    $ops = $past ? "IN ('approved','cancelled')" : "NOT IN ('approved','cancelled')";
    $st = mk_db()->prepare(MK_TASK_SELECT . "WHERE t.contractor_id = ? AND t.status $ops ORDER BY t.id DESC LIMIT " . (int) $limit);
    $st->execute([(int) $uid]);
    return $st->fetchAll();
}
/** A contractor's pending offers/quotes on jobs they do not hold yet. */
function mk_contractor_pending($uid) {
    $st = mk_db()->prepare("SELECT t.id, t.title, t.service, t.mode, t.status, o.kind, o.amount, o.status AS offer_status FROM task_offers o JOIN tasks t ON t.id = o.task_id
        WHERE o.contractor_id = ? AND o.status = 'sent' AND t.status IN ('open_for_bids','quote_pending','searching') ORDER BY o.id DESC LIMIT 50");
    $st->execute([(int) $uid]);
    return $st->fetchAll();
}

/** Contractor earnings summary + recent payment rows. Payout is on the pre-tax amount after commission. */
function mk_contractor_earnings($uid) {
    $db = mk_db();
    $s = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN state = 'authorized' THEN payout_amount END), 0) AS held,
        COALESCE(SUM(CASE WHEN state IN ('captured','released') AND payout_status = 'pending' THEN payout_amount END), 0) AS pending,
        COALESCE(SUM(CASE WHEN payout_status = 'paid' THEN payout_amount END), 0) AS paid
        FROM payments WHERE contractor_id = ?");
    $s->execute([(int) $uid]);
    $sum = $s->fetch();
    $r = $db->prepare("SELECT p.*, t.title FROM payments p JOIN tasks t ON t.id = p.task_id WHERE p.contractor_id = ? AND p.state <> 'unpaid' ORDER BY p.id DESC LIMIT 12");
    $r->execute([(int) $uid]);
    return ['sum' => $sum, 'rows' => $r->fetchAll()];
}
