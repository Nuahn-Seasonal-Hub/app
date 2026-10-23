<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/quotes.php: itemised mechanic quotes (parts + labour + call-out fee + GST/PST), the client's
// approve / ask / decline decision, and on-site REVISED quotes that need re-approval before any extra work.
// A revision stores only the NEW items; approving it re-sizes the escrow hold by that amount.

require_once __DIR__ . '/jobs.php';

/** Validate quote lines. @return array [lines, subtotal] */
function mk_quote_lines(array $items) {
    $lines = []; $sub = 0.0;
    foreach ($items as $it) {
        $desc = mk_clean($it['description'] ?? ($it[0] ?? ''), 200);
        $type = $it['type'] ?? ($it[1] ?? 'Parts');
        $qty = (float) ($it['qty'] ?? ($it[2] ?? 1));
        $price = (float) ($it['unit_price'] ?? ($it[3] ?? 0));
        if ($desc === '' && $price == 0.0) continue;
        if (!in_array($type, ['Parts', 'Labour', 'Fee'], true)) throw new MkError('Each line must be Parts, Labour or a Fee.');
        if ($desc === '') throw new MkError('Describe every line item.');
        if ($qty <= 0 || $qty > 999) throw new MkError('Quantity must be between 0.01 and 999.');
        if ($price < 0 || $price > 99999) throw new MkError('Unit prices must be between $0 and $99,999.');
        $lines[] = ['type' => $type, 'description' => $desc, 'qty' => round($qty, 2), 'unit_price' => mk_money($price)];
        $sub += round($qty, 2) * mk_money($price);
        if (count($lines) > 25) throw new MkError('A quote can have up to 25 lines.');
    }
    if (!$lines) throw new MkError('Add at least one line item.');
    return [$lines, mk_money($sub)];
}

function mk_quote_items($quoteId) {
    $st = mk_db()->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort, id'); $st->execute([(int) $quoteId]);
    return $st->fetchAll();
}
function mk_quote($id) {
    $st = mk_db()->prepare('SELECT q.*, TIMESTAMPDIFF(MINUTE, NOW(), q.expires_at) AS left_min, u.name AS contractor_name FROM quotes q JOIN users u ON u.id = q.contractor_id WHERE q.id = ?'); $st->execute([(int) $id]);
    return $st->fetch() ?: null;
}
/** Current (not superseded) initial quotes for a task, with items. */
function mk_quotes_for_task($taskId) {
    $st = mk_db()->prepare("SELECT q.*, TIMESTAMPDIFF(MINUTE, NOW(), q.expires_at) AS left_min, u.name AS contractor_name FROM quotes q JOIN users u ON u.id = q.contractor_id WHERE q.task_id = ? AND q.kind = 'initial' AND q.status <> 'superseded' ORDER BY q.total, q.id");
    $st->execute([(int) $taskId]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) { $r['items'] = mk_quote_items($r['id']); }
    return $rows;
}
function mk_revisions_for_task($taskId) {
    $st = mk_db()->prepare("SELECT q.* FROM quotes q WHERE q.task_id = ? AND q.kind = 'revision' ORDER BY q.id DESC"); $st->execute([(int) $taskId]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) { $r['items'] = mk_quote_items($r['id']); }
    return $rows;
}

function mk_quote_insert($taskId, $offerId, $contractorId, $kind, $lines, $sub, array $f) {
    $db = mk_db();
    [$gst, $pst] = mk_tax($sub);
    $v = $db->prepare('SELECT COALESCE(MAX(version),0) + 1 FROM quotes WHERE task_id = ? AND contractor_id = ? AND kind = ?'); $v->execute([(int) $taskId, (int) $contractorId, $kind]);
    $version = (int) $v->fetchColumn();
    $db->prepare('INSERT INTO quotes (task_id, offer_id, contractor_id, version, kind, status, scope, includes_text, excludes_text, found_note, windows, subtotal, gst, pst, total, expires_at)
        VALUES (?,?,?,?,?, \'sent\', ?,?,?,?,?,?,?,?,?, ' . ($kind === 'initial' ? 'DATE_ADD(NOW(), INTERVAL ' . (int) MK_QUOTE_VALID_HOURS . ' HOUR)' : 'NULL') . ')')
       ->execute([(int) $taskId, $offerId, (int) $contractorId, $version, $kind, mk_clean($f['scope'] ?? '', 1500), mk_clean($f['includes'] ?? '', 500), mk_clean($f['excludes'] ?? '', 500), mk_clean($f['found'] ?? '', 500), mk_clean($f['windows'] ?? '', 600), $sub, $gst, $pst, mk_money($sub + $gst + $pst)]);
    $qid = (int) $db->lastInsertId();
    $ins = $db->prepare('INSERT INTO quote_items (quote_id, type, description, qty, unit_price, sort) VALUES (?,?,?,?,?,?)');
    foreach ($lines as $i => $l) $ins->execute([$qid, $l['type'], $l['description'], $l['qty'], $l['unit_price'], $i]);
    return $qid;
}

/** A vetted mechanic quotes a scheduled repair. Re-sending replaces their previous quote (version + 1). */
function mk_quote_send($taskId, $techId, array $items, array $fields = []) {
    $t = mk_task($taskId);
    if (!$t || $t['mode'] !== 'scheduled') throw new MkError('That job was not found.', 404);
    mk_require_vetted($techId, 'mobile-mechanics');
    if ((int) $t['client_id'] === (int) $techId) throw new MkError('You cannot quote your own request.', 403);
    if ($t['status'] !== 'quote_pending') throw new MkError('This request is no longer waiting for quotes.', 409);
    [$lines, $sub] = mk_quote_lines($items);
    if ($sub < MK_MIN_PRICE || $sub > MK_MAX_PRICE) throw new MkError('The quote subtotal must be between $' . MK_MIN_PRICE . ' and $' . MK_MAX_PRICE . '.');
    $wins = array_slice(array_values(array_filter(array_map(fn($w) => mk_clean($w, 80), preg_split('/[\r\n|]+/', (string) ($fields['windows'] ?? ''))))), 0, 6);
    if (!$wins) throw new MkError('Offer at least one time window (one per line).');
    $fields['windows'] = implode('|', $wins);
    $db = mk_db();
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE quotes SET status = 'superseded' WHERE task_id = ? AND contractor_id = ? AND kind = 'initial' AND status IN ('sent','question')")->execute([(int) $taskId, (int) $techId]);
        [$g, $p] = mk_tax($sub);
        $db->prepare("INSERT INTO task_offers (task_id, contractor_id, kind, amount, message, status) VALUES (?,?, 'quote', ?, ?, 'sent')
            ON DUPLICATE KEY UPDATE kind = 'quote', amount = VALUES(amount), message = VALUES(message), status = 'sent', updated_at = NOW()")->execute([(int) $taskId, (int) $techId, $sub, mk_clean($fields['scope'] ?? '', 200)]);
        $oid = $db->prepare('SELECT id FROM task_offers WHERE task_id = ? AND contractor_id = ?'); $oid->execute([(int) $taskId, (int) $techId]); $oid = (int) $oid->fetchColumn();
        $qid = mk_quote_insert($taskId, $oid, $techId, 'initial', $lines, $sub, $fields);
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    mk_audit('quote.sent', 'task', $taskId, 'quote ' . $qid . ' subtotal ' . number_format($sub, 2), (int) $techId, 'provider');
    mk_notify($t['client_id'], $taskId, 'quote', 'You have a quote', 'An approved mechanic sent an itemised quote for ' . $t['title'] . '.', nu_url('public/quote.php') . '?task=' . (int) $taskId);
    return $qid;
}

/**
 * Client decision on an initial quote: approve (holds the funds and confirms), question (opens the thread), decline.
 * @return array|null mk_offer_accept() result when approved
 */
function mk_quote_decide($quoteId, $clientId, $decision, array $opts = []) {
    $q = mk_quote($quoteId);
    if (!$q || $q['kind'] !== 'initial') throw new MkError('That quote was not found.', 404);
    $t = mk_task($q['task_id']);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That quote was not found.', 404);
    if (!in_array($q['status'], ['sent', 'question'], true) || $t['status'] !== 'quote_pending') throw new MkError('This quote can no longer be changed.', 409);
    $db = mk_db();
    if ($decision === 'approve') {
        if (empty($opts['window'])) throw new MkError('Choose a time window to approve.');
        return mk_offer_accept($t['id'], $q['offer_id'], $clientId, $opts);
    }
    if ($decision === 'question') {
        $msg = mk_clean($opts['message'] ?? '', 800);
        if ($msg === '') throw new MkError('Type your question for the mechanic.');
        mk_message_send($t['id'], $clientId, 'Question about the quote: ' . $msg);
        $db->prepare("UPDATE quotes SET status = 'question' WHERE id = ?")->execute([(int) $quoteId]);
        mk_notify($q['contractor_id'], $t['id'], 'question', 'Question about your quote', $msg, nu_url('public/track.php') . '?id=' . (int) $t['id']);
        mk_audit('quote.question', 'task', $t['id'], 'quote ' . $quoteId, (int) $clientId, 'client');
        return null;
    }
    if ($decision === 'decline') {
        $db->prepare("UPDATE quotes SET status = 'declined', decided_at = NOW() WHERE id = ?")->execute([(int) $quoteId]);
        $db->prepare("UPDATE task_offers SET status = 'declined' WHERE id = ?")->execute([(int) $q['offer_id']]);
        mk_notify($q['contractor_id'], $t['id'], 'declined', 'Quote declined', $t['title'], nu_url('public/open_jobs.php'));
        mk_audit('quote.declined', 'task', $t['id'], 'quote ' . $quoteId, (int) $clientId, 'client');
        return null;
    }
    throw new MkError('Unknown decision.');
}

function mk_pending_revision($taskId) {
    $st = mk_db()->prepare("SELECT * FROM quotes WHERE task_id = ? AND kind = 'revision' AND status = 'sent' ORDER BY id DESC LIMIT 1"); $st->execute([(int) $taskId]);
    $r = $st->fetch();
    if ($r) $r['items'] = mk_quote_items($r['id']);
    return $r ?: null;
}

/** On-site finding: the technician adds work. The job PAUSES until the client re-approves; no extra work before that. */
function mk_quote_revise($taskId, $techId, array $items, $found) {
    $t = mk_task($taskId);
    if (!$t || (int) $t['contractor_id'] !== (int) $techId || $t['service'] !== 'mobile-mechanics') throw new MkError('That job was not found.', 404);
    if (!in_array($t['status'], ['arrived', 'in_progress'], true)) throw new MkError('A revised quote can be sent once you have arrived and before you submit the work.', 409);
    if (mk_pending_revision($taskId)) throw new MkError('A revised quote is already waiting for the client.', 409);
    $found = mk_clean($found, 500); if ($found === '') throw new MkError('Say what you found on site.');
    [$lines, $sub] = mk_quote_lines($items);
    $qid = mk_quote_insert($taskId, $t['accepted_offer_id'], $techId, 'revision', $lines, $sub, ['found' => $found]);
    if (!mk_transition($taskId, [$t['status']], 'paused', [], 'revised quote ' . $qid . ' awaiting client')) throw new MkError('That job changed. Refresh and try again.', 409);
    mk_notify($t['client_id'], $taskId, 'revision', 'Extra work needs your approval', 'Your mechanic found something on site. Work is paused until you decide.', nu_url('public/track.php') . '?id=' . (int) $taskId . '#revision');
    return $qid;
}

/** Client decides on the revision. Approve re-sizes the hold, then work resumes. Decline resumes the ORIGINAL scope only. */
function mk_revision_decide($quoteId, $clientId, $approve) {
    $q = mk_quote($quoteId);
    if (!$q || $q['kind'] !== 'revision') throw new MkError('That revised quote was not found.', 404);
    $t = mk_task($q['task_id']);
    if (!$t || (int) $t['client_id'] !== (int) $clientId) throw new MkError('That revised quote was not found.', 404);
    if ($q['status'] !== 'sent' || $t['status'] !== 'paused') throw new MkError('This revised quote can no longer be changed.', 409);
    if ($approve) {
        mk_payment_adjust($t['id'], (float) $q['subtotal']);
        mk_db()->prepare("UPDATE quotes SET status = 'approved', decided_at = NOW() WHERE id = ? AND status = 'sent'")->execute([(int) $quoteId]);
    } else {
        mk_db()->prepare("UPDATE quotes SET status = 'declined', decided_at = NOW() WHERE id = ? AND status = 'sent'")->execute([(int) $quoteId]);
    }
    mk_transition($t['id'], ['paused'], 'in_progress', [], 'revision ' . ($approve ? 'approved' : 'declined'));
    mk_audit('quote.revision_' . ($approve ? 'approved' : 'declined'), 'task', $t['id'], 'quote ' . $quoteId, (int) $clientId, 'client');
    mk_notify($t['contractor_id'], $t['id'], 'revision', $approve ? 'Extra work approved' : 'Extra work declined', $approve ? 'You can add it to the job now. The hold was increased.' : 'Finish the original scope only.', nu_url('public/track.php') . '?id=' . (int) $t['id']);
}
