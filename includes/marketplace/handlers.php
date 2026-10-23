<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/handlers.php: one handler per POST action, dispatched by actions/marketplace.php.
// The router has already checked: POST, signed in, CSRF token, and that the action exists for the caller's role.
// Each handler re-checks ownership through the jobs.php functions and returns [redirect URL, success message|null].
// Throw MkError to refuse: 403 / 404 return that HTTP status, 409 / 422 flash the message and return to the form.

require_once __DIR__ . '/bootstrap.php';

/** do => [allowed roles, handler]. 'staff' = superadmin/manager/admin. */
function mk_routes() {
    $c = ['client']; $p = ['provider']; $s = ['staff']; $any = ['client', 'provider', 'staff'];
    return [
        // client
        'snow_post'        => [$c, 'mk_h_snow_post'],
        'mech_request'     => [$c, 'mk_h_mech_request'],
        'offer_accept'     => [$c, 'mk_h_offer_accept'],
        'task_boost'       => [$c, 'mk_h_task_boost'],
        'task_cancel'      => [$c, 'mk_h_task_cancel'],
        'job_approve'      => [$c, 'mk_h_job_approve'],
        'dispute_open'     => [$c, 'mk_h_dispute_open'],
        'review_add'       => [$c, 'mk_h_review_add'],
        'quote_decide'     => [$c, 'mk_h_quote_decide'],
        'revision_decide'  => [$c, 'mk_h_revision_decide'],
        // provider
        'offer_send'       => [$p, 'mk_h_offer_send'],
        'offer_withdraw'   => [$p, 'mk_h_offer_withdraw'],
        'roadside_accept'  => [$p, 'mk_h_roadside_accept'],
        'job_step'         => [$p, 'mk_h_job_step'],
        'photo_upload'     => [$p, 'mk_h_photo_upload'],
        'job_submit'       => [$p, 'mk_h_job_submit'],
        'job_withdraw'     => [$p, 'mk_h_job_withdraw'],
        'quote_send'       => [$p, 'mk_h_quote_send'],
        'quote_revise'     => [$p, 'mk_h_quote_revise'],
        'profile_save'     => [$p, 'mk_h_profile_save'],
        'doc_upload'       => [$p, 'mk_h_doc_upload'],
        'profile_submit'   => [$p, 'mk_h_profile_submit'],
        'online_toggle'    => [$p, 'mk_h_online_toggle'],
        // shared
        'message_send'     => [$any, 'mk_h_message_send'],
        'notif_read'       => [$any, 'mk_h_notif_read'],
        // operations
        'vetting_decide'   => [$s, 'mk_h_vetting_decide'],
        'dispute_resolve'  => [$s, 'mk_h_dispute_resolve'],
        'snow_event'       => [$s, 'mk_h_snow_event'],
        'payout_paid'      => [$s, 'mk_h_payout_paid'],
        'nudge'            => [$s, 'mk_h_nudge'],
    ];
}

function mk_in($k, $d = '') { return $_POST[$k] ?? $d; }
function mk_int($k) { return (int) ($_POST[$k] ?? 0); }
function mk_page($p) { return nu_url('public/' . $p); }

// ---------- client ----------
function mk_h_snow_post() {
    $id = mk_task_create_snow(mk_uid(), ['address' => mk_in('loc_address'), 'lat' => mk_in('loc_lat'), 'lng' => mk_in('loc_lng'), 'size' => mk_in('size'), 'sqft' => mk_in('sqft'),
        'walk' => mk_in('walk'), 'steps' => mk_in('steps'), 'ice' => mk_in('ice'), 'plan' => mk_in('plan'), 'budget' => mk_in('budget'), 'boost' => mk_in('boost'), 'notes' => mk_in('notes')]);
    return [mk_page('offers.php') . '?task=' . $id . '&posted=1', 'Your task is posted. Verified contractors nearby have been alerted.'];
}
function mk_h_mech_request() {
    $reserved = ['_csrf', 'do', 'svc', 'path', 'vehicle', 'mr_loc_address', 'mr_loc_lat', 'mr_loc_lng', 'test_card', 'back'];
    $extras = array_diff_key($_POST, array_flip($reserved));
    $r = mk_task_create_mech(mk_uid(), ['svc' => mk_in('svc'), 'vehicle' => $_POST['vehicle'] ?? [], 'address' => mk_in('mr_loc_address'), 'lat' => mk_in('mr_loc_lat'), 'lng' => mk_in('mr_loc_lng'),
        'extras' => $extras, 'notes' => mk_in('notes'), 'test_card' => mk_in('test_card')]);
    $id = $r['id'];
    if (!empty($_FILES['photos'])) {
        try { mk_photo_store($id, mk_uid(), 'issue', 'photos'); } catch (MkError $e) { flashWarning('Your request was sent, but a photo was skipped: ' . $e->getMessage()); }
    }
    if ($r['pay']) return [mk_page('pay.php') . '?task=' . $id, null];
    return [mk_page('mechanic_request.php') . '?task=' . $id, mk_in('path') === 'roadside' ? 'Request sent. Nearby technicians are being alerted.' : 'Quote request sent. Approved mechanics will send itemised quotes.'];
}
function mk_h_offer_accept() {
    $r = mk_offer_accept(mk_int('task_id'), mk_int('offer_id'), mk_uid(), ['test_card' => mk_in('test_card')]);
    if ($r['status'] === 'pay') return [mk_page('pay.php') . '?task=' . $r['task_id'], null];
    return [mk_page('track.php') . '?id=' . $r['task_id'], 'Offer accepted. Funds are held until you approve the finished work.'];
}
function mk_h_task_boost() {
    mk_task_boost(mk_int('task_id'), mk_uid(), mk_in('boost'));
    return [mk_page('offers.php') . '?task=' . mk_int('task_id'), 'Boost updated. Contractors nearby were alerted.'];
}
function mk_h_task_cancel() {
    mk_job_cancel_client(mk_int('task_id'), mk_uid(), mk_in('reason'));
    return [mk_page('bookings.php'), 'The job was cancelled. Any held funds were released.'];
}
function mk_h_job_approve() {
    mk_job_approve(mk_int('task_id'), 'client', mk_uid());
    return [mk_page('approve.php') . '?id=' . mk_int('task_id') . '&done=1', 'Approved. Payment was captured and the contractor will be paid.'];
}
function mk_h_dispute_open() {
    $tid = mk_int('task_id');
    $a = mk_task_access($tid);
    if (!$a || $a[1] !== 'client') throw new MkError('That job was not found.', 404);
    mk_dispute_open($tid, mk_uid(), mk_in('reason'), mk_in('details'), mk_in('wanted'));
    if (!empty($_FILES['photo'])) { try { mk_photo_store($tid, mk_uid(), 'dispute', 'photo'); } catch (MkError $e) { flashWarning('Your report was sent, but a photo was skipped: ' . $e->getMessage()); } }
    return [mk_page('track.php') . '?id=' . $tid, 'Thanks. Nuahn will review. The payment stays held until it is resolved.'];
}
function mk_h_review_add() {
    mk_review_add(mk_int('task_id'), mk_uid(), mk_in('rating'), mk_in('comment'));
    return [mk_page('approve.php') . '?id=' . mk_int('task_id') . '&done=1', 'Thanks for your review.'];
}
function mk_h_quote_decide() {
    $d = mk_in('decision');
    $r = mk_quote_decide(mk_int('quote_id'), mk_uid(), $d, ['window' => mk_in('window'), 'message' => mk_in('message'), 'test_card' => mk_in('test_card')]);
    $tid = mk_int('task_id');
    if ($d === 'approve' && $r) {
        if ($r['status'] === 'pay') return [mk_page('pay.php') . '?task=' . $r['task_id'], null];
        return [mk_page('track.php') . '?id=' . $r['task_id'], 'Quote approved. Funds are held until you approve the finished work.'];
    }
    return [mk_page('quote.php') . '?task=' . $tid, $d === 'question' ? 'Your question was sent.' : 'Quote declined.'];
}
function mk_h_revision_decide() {
    mk_revision_decide(mk_int('quote_id'), mk_uid(), mk_in('decision') === 'approve');
    return [mk_page('track.php') . '?id=' . mk_int('task_id'), mk_in('decision') === 'approve' ? 'Extra work approved. The held amount was updated.' : 'Extra work declined. The original job continues.'];
}

// ---------- provider ----------
function mk_h_offer_send() {
    $counter = mk_in('kind') === 'counter';
    mk_offer_send(mk_int('task_id'), mk_uid(), mk_in('amount'), mk_in('message'), mk_in('eta'), $counter);
    return [mk_page('open_jobs.php'), $counter ? 'Counter-offer sent.' : 'Offer sent at the posted price.'];
}
function mk_h_offer_withdraw() { mk_offer_withdraw(mk_int('task_id'), mk_uid()); return [mk_page('open_jobs.php'), 'Offer withdrawn.']; }
function mk_h_roadside_accept() {
    mk_roadside_accept(mk_int('task_id'), mk_uid(), mk_in('eta'));
    return [mk_page('track.php') . '?id=' . mk_int('task_id'), 'You have the job. Head to the client now.'];
}
function mk_h_job_step() {
    mk_job_step(mk_int('task_id'), mk_uid(), mk_in('step'));
    return [mk_page('track.php') . '?id=' . mk_int('task_id'), 'Status updated.'];
}
function mk_h_photo_upload() {
    $n = mk_job_photo_add(mk_int('task_id'), mk_uid(), mk_in('kind'));
    return [mk_page('track.php') . '?id=' . mk_int('task_id') . '#photos', $n . ' photo' . ($n === 1 ? '' : 's') . ' added.'];
}
function mk_h_job_submit() {
    mk_job_submit(mk_int('task_id'), mk_uid(), mk_in('note'), (array) ($_POST['checklist'] ?? []));
    return [mk_page('track.php') . '?id=' . mk_int('task_id'), 'Submitted for approval. The client has been notified.'];
}
function mk_h_job_withdraw() {
    mk_job_cancel_contractor(mk_int('task_id'), mk_uid(), mk_in('reason'));
    return [mk_page('open_jobs.php'), 'You withdrew from the job. It was reopened for others.'];
}
function mk_h_quote_send() {
    $items = [];
    foreach ((array) ($_POST['item_desc'] ?? []) as $i => $d) {
        $items[] = ['type' => $_POST['item_type'][$i] ?? 'Parts', 'description' => $d, 'qty' => $_POST['item_qty'][$i] ?? 1, 'unit_price' => $_POST['item_price'][$i] ?? 0];
    }
    mk_quote_send(mk_int('task_id'), mk_uid(), $items, ['scope' => mk_in('scope'), 'includes' => mk_in('includes'), 'excludes' => mk_in('excludes'), 'windows' => mk_in('windows')]);
    return [mk_page('open_jobs.php'), 'Quote sent to the client.'];
}
function mk_h_quote_revise() {
    $items = [];
    foreach ((array) ($_POST['item_desc'] ?? []) as $i => $d) {
        $items[] = ['type' => $_POST['item_type'][$i] ?? 'Parts', 'description' => $d, 'qty' => $_POST['item_qty'][$i] ?? 1, 'unit_price' => $_POST['item_price'][$i] ?? 0];
    }
    mk_quote_revise(mk_int('task_id'), mk_uid(), $items, mk_in('found'));
    return [mk_page('track.php') . '?id=' . mk_int('task_id'), 'Revised quote sent. The job is paused until the client approves it.'];
}
function mk_h_profile_save() {
    mk_profile_save(mk_uid(), ['trades' => $_POST['trades'] ?? [], 'headline' => mk_in('headline'), 'about' => mk_in('about'), 'equipment' => mk_in('equipment'),
        'base_address' => mk_in('base_address'), 'lat' => mk_in('base_lat'), 'lng' => mk_in('base_lng'), 'radius_km' => mk_in('radius_km')]);
    return [mk_page('contractor_setup.php'), 'Profile saved.'];
}
function mk_h_doc_upload() {
    mk_profile_ensure(mk_uid());
    mk_doc_store(mk_uid(), mk_in('kind'), 'doc', mk_in('expires_on') ?: null);
    return [mk_page('contractor_setup.php'), 'Document uploaded. It is checked when you submit your application.'];
}
function mk_h_profile_submit() { mk_profile_submit(mk_uid()); return [mk_page('contractor_setup.php'), 'Application submitted. Nuahn will review your documents.']; }
function mk_h_online_toggle() { mk_profile_online(mk_uid(), mk_in('online') === '1'); return [mk_page('provider_home.php'), mk_in('online') === '1' ? 'You are online for new jobs.' : 'You are offline. No new job alerts.']; }

// ---------- shared ----------
function mk_h_message_send() {
    $tid = mk_int('task_id');
    $a = mk_task_access($tid);
    if (!$a || !in_array($a[1], ['client', 'contractor', 'bidder', 'staff'], true)) throw new MkError('That job was not found.', 404);
    [$t, $rel] = $a;
    mk_message_send($tid, mk_uid(), mk_in('body'));
    $to = $rel === 'client' ? ($t['contractor_id'] ?: null) : $t['client_id'];
    if ($rel === 'client' && !$to) { // no contractor yet: tell every bidder
        foreach (mk_db()->query('SELECT contractor_id FROM task_offers WHERE task_id = ' . (int) $tid)->fetchAll(PDO::FETCH_COLUMN) as $cid) mk_notify($cid, $tid, 'message', 'New message', mk_clean(mk_in('body'), 100), mk_job_link($t, 'contractor'));
    } else { mk_notify($to, $tid, 'message', 'New message', mk_clean(mk_in('body'), 100), mk_job_link($t, $rel === 'client' ? 'contractor' : 'client')); }
    return [mk_safe_back(mk_page('track.php') . '?id=' . $tid), 'Message sent.'];
}
function mk_h_notif_read() {
    if (mk_in('all')) { mk_db()->prepare('UPDATE job_notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL')->execute([mk_uid()]); }
    else { mk_db()->prepare('UPDATE job_notifications SET read_at = NOW() WHERE user_id = ? AND id = ?')->execute([mk_uid(), mk_int('id')]); }
    return [mk_page('updates.php'), null];
}

// ---------- operations ----------
function mk_h_vetting_decide() {
    mk_vetting_decide(mk_int('user_id'), mk_in('decision'), ['chk_identity' => mk_in('chk_identity'), 'chk_insurance' => mk_in('chk_insurance'), 'chk_licence' => mk_in('chk_licence'), 'chk_cert' => mk_in('chk_cert')], mk_in('note'));
    return [mk_page('operations.php') . '?view=vetting', 'Decision saved.'];
}
function mk_h_dispute_resolve() {
    mk_dispute_resolve(mk_int('dispute_id'), mk_in('resolution'), mk_in('share'), mk_in('note'));
    return [mk_page('operations.php') . '?view=disputes', 'Dispute resolved and the payment was settled.'];
}
function mk_h_snow_event() {
    [$n, $skip] = mk_snow_event(mk_in('event_key'), mk_in('snowfall_cm'), mk_in('area'), mk_uid());
    return [mk_page('operations.php') . '?view=snow', $n . ' seasonal visit' . ($n === 1 ? '' : 's') . ' created.'];
}
function mk_h_payout_paid() { mk_payout_mark_paid(mk_int('task_id'), mk_in('ref')); return [mk_page('operations.php') . '?view=payouts', 'Payout marked as paid.']; }
function mk_h_nudge() {
    mk_require_staff();
    $n = mk_broadcast(mk_int('task_id'));
    mk_audit('job.nudged', 'task', mk_int('task_id'), $n . ' contractors alerted');
    return [mk_page('operations.php') . '?view=open', $n . ' nearby contractor' . ($n === 1 ? ' was' : 's were') . ' alerted.'];
}

/** Same-site relative back link from the form (blocks open redirects). */
function mk_safe_back($default) {
    $b = mk_safe_next($_POST['back'] ?? '');
    if ($b === '') return $default;
    $b = ltrim($b, '/');
    return nu_url(strpos($b, 'public/') === 0 ? $b : 'public/' . $b);
}
