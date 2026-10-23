<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/operations.php: Operations console for the independent-contractor marketplace, on live data. STAFF ONLY (superadmin / manager).
// ?view=overview | open (open jobs, nudge) | vetting (approve / reject / request changes, persisted; only approved contractors can offer)
//      | disputes (resolve by release / refund / split) | payouts (mark payouts paid) | snow (record a snowfall event for seasonal plans) | audit (append-only log).
// Every change posts to actions/marketplace.php (CSRF + staff role check there as well).
require_once("../config/init.php");
requireLogin(['manager', 'superadmin', 'admin']);
require_once("../includes/marketplace/view.php");
if (!mk_ready()) { include_once("../includes/header.php"); echo '<main class="screen"><div class="sv-card"><h1>Marketplace tables are not installed</h1><p>Run database/migrations.sql.</p></div></main>'; include_once("../includes/footer.php"); exit; }

$view = (string) ($_GET['view'] ?? 'overview');
if (!in_array($view, ['overview', 'open', 'vetting', 'disputes', 'payouts', 'snow', 'audit'], true)) $view = 'overview';
$nav = nu_ops_nav();
$titles = ['overview' => 'Operations overview', 'open' => 'Open jobs', 'vetting' => 'Vetting queue', 'disputes' => 'Disputes', 'payouts' => 'Payouts', 'snow' => 'Snow events', 'audit' => 'Audit log'];
$cnt = mk_ops_counts();
$chk = ['chk_identity' => 'Identity', 'chk_insurance' => 'Insurance', 'chk_licence' => 'Licence', 'chk_cert' => 'Mechanic certification'];
$chkOpt = ['missing' => 'Missing', 'review' => 'Needs review', 'ok' => 'Verified', 'rejected' => 'Rejected', 'na' => 'Not required'];
$chkTone = ['ok' => 'success', 'review' => 'warning', 'missing' => 'danger', 'rejected' => 'danger', 'na' => ''];
$svcName = ['snow-removal' => 'Winter snow removal', 'mobile-mechanics' => 'Mobile mechanics'];
$db = mk_db();
$kp = [
    ['megaphone', $cnt['open'], 'Open jobs', 'blue'],
    ['person-vcard', $cnt['vetting'], 'To vet', 'warning'],
    ['flag-fill', $cnt['disputes'], 'Disputes open', 'danger'],
    ['wallet2', $cnt['payouts'], 'Payouts pending', 'success'],
];
if ($view === 'open') $openJobs = mk_ops_open_jobs();
if ($view === 'vetting') $queue = mk_vetting_queue();
if ($view === 'disputes') { $dOpen = mk_disputes_list('open'); $dDone = array_slice(mk_disputes_list('resolved'), 0, 10); }
if ($view === 'payouts') $payouts = mk_ops_payouts();
if ($view === 'audit') { $aq = trim((string) ($_GET['q'] ?? '')); $audit = mk_ops_audit(150, $aq); }
if ($view === 'snow') $events = $db->query('SELECT * FROM snow_events ORDER BY id DESC LIMIT 20')->fetchAll();
if ($view === 'overview') {
    $ov = array_slice(mk_ops_open_jobs(), 0, 3);
    $ovq = array_slice(mk_vetting_queue(), 0, 3);
    $ovd = array_slice(mk_disputes_list('open'), 0, 3);
    $online = $db->query("SELECT p.user_id, u.name, p.trades FROM contractor_profiles p JOIN users u ON u.id = p.user_id WHERE p.is_online = 1 AND p.vetting_status = 'approved' ORDER BY u.name LIMIT 8")->fetchAll();
    $payStates = $db->query("SELECT state, COUNT(*) n FROM payments GROUP BY state")->fetchAll(PDO::FETCH_KEY_PAIR);
}
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, $view === 'payouts' ? 'payments' : $view, 'Operations') ?>
  <main class="console__main op" id="ops">
    <header class="op-head">
      <div><h1><?= nu_e($titles[$view]) ?></h1><?php if ($view === 'overview'): ?><p class="op-sub">Contractors pick their own jobs. Operations keeps the marketplace safe: who may offer, and what happens when work is contested.</p><?php endif; ?></div>
    </header>
    <?= mk_test_note() ?>
    <nav class="op-tabs" aria-label="Operations views">
<?php foreach ($titles as $k => $l): ?>
      <a href="?view=<?= $k ?>"<?= $k === $view ? ' aria-current="page"' : '' ?>><?= nu_e($l) ?><?php if ($k === 'open' && $cnt['open']): ?> <b><?= (int) $cnt['open'] ?></b><?php elseif ($k === 'vetting' && $cnt['vetting']): ?> <b><?= (int) $cnt['vetting'] ?></b><?php elseif ($k === 'disputes' && $cnt['disputes']): ?> <b><?= (int) $cnt['disputes'] ?></b><?php elseif ($k === 'payouts' && $cnt['payouts']): ?> <b><?= (int) $cnt['payouts'] ?></b><?php endif; ?></a>
<?php endforeach; ?>
    </nav>

<?php if ($view === 'overview'): ?>
    <div class="op-kpis">
<?php foreach ($kp as $k): ?>
      <div class="op-kpi op-kpi--<?= $k[3] ?>"><span class="op-kpi__ic"><?= nu_icon($k[0]) ?></span><span><b><?= (int) $k[1] ?></b><?= nu_e($k[2]) ?></span></div>
<?php endforeach; ?>
    </div>
    <div class="op-row">
      <section class="op-card">
        <h2 class="op-h2">Open jobs <a class="op-more" href="?view=open">View all</a></h2>
        <ul class="op-mini-list"><?php foreach ($ov as $j): ?><li><span><b><?= nu_e($j['title']) ?></b><small><?= nu_e(mk_area($j['address'])) ?> · <?= (int) $j['offers'] ?> offer<?= (int) $j['offers'] === 1 ? '' : 's' ?> · <?= nu_e(mk_ago($j['age_min'] * 60)) ?></small></span><?= nu_booking_chip($j['status']) ?></li><?php endforeach; ?><?php if (!$ov): ?><li><span>Nothing open right now.</span></li><?php endif; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Vetting queue <a class="op-more" href="?view=vetting">Review</a></h2>
        <ul class="op-mini-list"><?php foreach ($ovq as $a): ?><li><span><b><?= nu_e($a['name']) ?></b><small><?= nu_e($a['trades']) ?> · <?= $a['waiting_h'] !== null ? (int) $a['waiting_h'] . ' h waiting' : 'draft' ?></small></span><span class="chip chip--warning"><?= nu_e(str_replace('_', ' ', $a['vetting_status'])) ?></span></li><?php endforeach; ?><?php if (!$ovq): ?><li><span>Queue is empty.</span></li><?php endif; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Disputes <a class="op-more" href="?view=disputes">Open</a></h2>
        <ul class="op-mini-list"><?php foreach ($ovd as $d): ?><li><span><b>DSP-<?= 1000 + (int) $d['id'] ?> · <?= nu_e($d['reason']) ?></b><small>$<?= number_format((float) $d['pay_total'], 2) ?> held · <?= nu_e(mk_ago($d['age_h'] * 3600)) ?></small></span><?= nu_payment_chip('disputed') ?></li><?php endforeach; ?><?php if (!$ovd): ?><li><span>No open disputes.</span></li><?php endif; ?></ul>
      </section>
    </div>
    <div class="op-row">
      <section class="op-card">
        <h2 class="op-h2">Contractors online</h2>
        <ul class="op-teams"><?php foreach ($online as $t): ?><li><?= nu_icon('person-check') ?><span><?= nu_e($t['name']) ?> <small><?= nu_e($t['trades']) ?></small></span><a class="linkbtn" href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= (int) $t['user_id'] ?>">Profile</a></li><?php endforeach; ?><?php if (!$online): ?><li><span>No approved contractors are online.</span></li><?php endif; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Snow events <a class="op-more" href="?view=snow">Record</a></h2>
        <div class="op-wx"><?= nu_icon('cloud-snow') ?><div><b>Seasonal plans trigger at <?= (int) MK_SEASONAL_TRIGGER_CM ?> cm</b><span>Record a snowfall event and a visit is created for every confirmed plan. A forecast feed is not connected yet.</span></div></div>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Service settings</h2>
        <ul class="op-svcs"><?php foreach (nu_services_by_status('live') as $k => $s): ?><li><a href="<?= nu_e(nu_url('public/service_settings.php') . '?s=' . $k) ?>"><?= nu_icon($s['icon']) ?><span><?= nu_e($s['name']) ?></span></a></li><?php endforeach; ?></ul>
      </section>
    </div>
    <section class="op-card op-pay" id="payments">
      <h2 class="op-h2">Payments <small>Separate from job status</small></h2>
      <div class="op-paylist"><?php foreach ($payStates as $st => $n): ?><span><?= nu_payment_chip($st) ?><b><?= (int) $n ?></b></span><?php endforeach; ?></div>
      <p class="op-mini"><?= nu_icon('shield-lock') ?> Card entry is handled by the payment processor’s hosted fields: Nuahn only stores a token. Commission: <?= (int) MK_COMMISSION_PCT ?>% of the pre-tax job amount, set in includes/marketplace/config.php.</p>
    </section>

<?php elseif ($view === 'open'): ?>
    <section class="op-card op-queue" id="queue">
      <h2 class="op-h2">Open jobs <small>Contractors accept or counter-bid; operations only watches and can nudge</small></h2>
      <div class="op-tblwrap">
      <table class="op-tbl mk-tbl">
        <thead><tr><th>Task</th><th>Service</th><th>Area</th><th>Budget</th><th>Offers</th><th>Posted</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($openJobs as $j): ?>
          <tr><td><a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $j['id'] ?>"><b><?= nu_e(mk_ref($j)) ?></b></a><small class="op-sm"><?= nu_e($j['title']) ?> · <?= nu_e($j['client_name']) ?></small></td><td><?= nu_e($svcName[$j['service']] ?? $j['service']) ?></td><td><?= nu_e(mk_area($j['address'])) ?></td><td><?= $j['target_budget'] ? '$' . (int) $j['target_budget'] : 'Quote' ?><?= (int) $j['boost'] ? ' <span class="chip chip--blue">+' . (int) $j['boost'] . '% boost</span>' : '' ?></td><td><?= (int) $j['offers'] ?> · <?= nu_booking_chip($j['status'], false) ?></td><td><?= nu_e(mk_ago($j['age_min'] * 60)) ?></td>
            <td><?= mk_form('nudge', ['task_id' => $j['id']], 'class="mk-inline"', 'operations.php?view=open') ?><button class="btn btn-outline btn-sm" type="submit">Nudge</button></form></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
<?php if (!$openJobs): nu_empty('megaphone', 'No open jobs', 'Posted tasks waiting for offers or quotes show here.'); endif; ?>
    </section>

<?php elseif ($view === 'vetting'): ?>
    <section class="op-card" id="vetting">
      <h2 class="op-h2">Applications to review <small>Identity, insurance, licence and mechanic certification</small></h2>
      <div class="vet-list">
<?php foreach ($queue as $a): $docs = mk_documents($a['user_id']); $isMech = strpos((string) $a['trades'], 'mobile-mechanics') !== false; ?>
        <article class="vet">
          <header><div><b><?= nu_e($a['name']) ?></b><span>ID <?= (int) $a['user_id'] ?> · <?= nu_e($a['trades']) ?> · <?= $a['waiting_h'] !== null ? (int) $a['waiting_h'] . ' h waiting' : 'draft' ?></span></div><span class="chip chip--warning"><?= nu_e(str_replace('_', ' ', $a['vetting_status'])) ?></span></header>
          <?php if (!empty($a['headline'])): ?><p class="vet__note"><?= nu_icon('person') ?><?= nu_e($a['headline']) ?></p><?php endif; ?>
          <?php if ($docs): ?><ul class="mk-docs"><?php foreach ($docs as $dc): ?><li><?= nu_icon('file-earmark-text') ?> <a href="<?= nu_e(nu_url('public/media.php')) ?>?d=<?= (int) $dc['id'] ?>" target="_blank" rel="noopener"><?= nu_e(ucfirst($dc['kind'])) ?></a><small><?= $dc['expires_on'] ? 'expires ' . nu_e($dc['expires_on']) : '' ?></small></li><?php endforeach; ?></ul><?php else: ?><p class="vet__note"><?= nu_icon('exclamation-circle') ?>No documents uploaded yet.</p><?php endif; ?>
          <?= mk_form('vetting_decide', ['user_id' => $a['user_id']], 'class="mk-form"', 'operations.php?view=vetting') ?>
            <ul class="vet__checks">
<?php foreach ($chk as $ck => $cl): ?>
              <li class="vet__c"><label for="<?= $ck . $a['user_id'] ?>"><?= nu_e($cl) ?></label>
                <select class="input" id="<?= $ck . $a['user_id'] ?>" name="<?= $ck ?>"><?php foreach ($chkOpt as $v => $l): ?><option value="<?= $v ?>"<?= $a[$ck] === $v ? ' selected' : '' ?>><?= nu_e($l) ?></option><?php endforeach; ?></select></li>
<?php endforeach; ?>
            </ul>
            <label class="vet__note" for="note<?= (int) $a['user_id'] ?>"><?= nu_icon('chat-left-text') ?> Note to the applicant (required for reject or changes)</label>
            <textarea class="input" id="note<?= (int) $a['user_id'] ?>" name="note" rows="2" maxlength="1000"><?= nu_e($a['vet_note'] ?? '') ?></textarea>
            <div class="op-acts">
              <button class="btn btn-primary btn-sm" name="decision" value="approve" type="submit">Approve</button>
              <button class="btn btn-outline btn-sm" name="decision" value="changes" type="submit">Request changes</button>
              <button class="btn btn-ghost btn-sm" name="decision" value="reject" type="submit">Reject</button>
              <button class="btn btn-ghost btn-sm" name="decision" value="review" type="submit">Mark in review</button>
            </div>
          </form>
        </article>
<?php endforeach; ?>
      </div>
<?php if (!$queue): nu_empty('person-vcard', 'Queue is empty', 'Submitted contractor applications show here.'); endif; ?>
      <p class="op-mini"><?= nu_icon('shield-check') ?> Only approved contractors see open jobs and can send offers. Every check must be Verified or Not required before Approve works.</p>
    </section>

<?php elseif ($view === 'disputes'): ?>
    <section class="op-card" id="disputes">
      <h2 class="op-h2">Disputes <small>Funds stay held until a decision</small></h2>
      <div class="vet-list">
<?php foreach ($dOpen as $d): $wl = ['redo' => 'Redo the work', 'partial' => 'Partial refund', 'refund' => 'Full refund'][$d['wanted']] ?? $d['wanted']; ?>
        <article class="vet">
          <header><div><b>DSP-<?= 1000 + (int) $d['id'] ?> · <?= nu_e($d['reason']) ?></b><span><?= nu_e($svcName[$d['service']] ?? '') ?> · <a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $d['task_id'] ?>"><?= nu_e(mk_ref(['service' => $d['service'], 'mode' => $d['mode'], 'id' => $d['task_id']])) ?></a> · opened <?= nu_e(mk_ago($d['age_h'] * 3600)) ?></span></div><?= nu_payment_chip('disputed') ?></header>
          <dl class="vet__dl">
            <div><dt>Client</dt><dd><?= nu_e($d['client_name']) ?></dd></div>
            <div><dt>Contractor</dt><dd><?php if ($d['contractor_id']): ?><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= (int) $d['contractor_id'] ?>"><?= nu_e($d['contractor_name']) ?></a><?php endif; ?></dd></div>
            <div><dt>Held</dt><dd>$<?= number_format((float) $d['pay_total'], 2) ?> (job $<?= number_format((float) $d['pay_subtotal'], 2) ?> + tax)</dd></div>
            <div><dt>Client wants</dt><dd><?= nu_e($wl) ?></dd></div>
          </dl>
          <?php if (!empty($d['details'])): ?><p class="vet__note"><?= nu_icon('chat-left-text') ?><?= nu_e($d['details']) ?></p><?php endif; ?>
          <?= mk_photo_grid($d['task_id'], 'before', 'No before photos') ?><?= mk_photo_grid($d['task_id'], 'after', 'No after photos') ?><?= mk_photo_grid($d['task_id'], 'dispute', '') ?>
          <?= mk_form('dispute_resolve', ['dispute_id' => $d['id']], 'class="mk-form"', 'operations.php?view=disputes') ?>
            <label for="dn<?= (int) $d['id'] ?>">Decision note (sent to both sides)</label>
            <textarea class="input" id="dn<?= (int) $d['id'] ?>" name="note" rows="2" maxlength="500" required></textarea>
            <label for="ds<?= (int) $d['id'] ?>">Contractor share for a split, before tax ($)</label>
            <input class="input" id="ds<?= (int) $d['id'] ?>" name="share" type="number" step="0.01" min="0" max="<?= nu_e($d['pay_subtotal']) ?>" placeholder="e.g. <?= nu_e(number_format((float) $d['pay_subtotal'] / 2, 2, '.', '')) ?>">
            <div class="op-acts">
              <button class="btn btn-primary btn-sm" name="resolution" value="release" type="submit">Release to contractor</button>
              <button class="btn btn-outline btn-sm" name="resolution" value="refund" type="submit">Refund client</button>
              <button class="btn btn-outline btn-sm" name="resolution" value="split" type="submit">Split</button>
            </div>
          </form>
        </article>
<?php endforeach; ?>
      </div>
<?php if (!$dOpen): nu_empty('flag', 'No open disputes', 'Disputes opened by clients show here with their evidence.'); endif; ?>
<?php if ($dDone): ?>
      <h3 class="pd-h3">Recently resolved</h3>
      <ul class="op-mini-list"><?php foreach ($dDone as $d): ?><li><span><b>DSP-<?= 1000 + (int) $d['id'] ?> · <?= nu_e($d['reason']) ?></b><small><?= nu_e(ucfirst((string) $d['resolution'])) ?><?= $d['contractor_share'] !== null ? ' · contractor share $' . number_format((float) $d['contractor_share'], 2) : '' ?> · <?= nu_e((string) $d['resolution_note']) ?></small></span><span class="chip chip--success">Resolved</span></li><?php endforeach; ?></ul>
<?php endif; ?>
      <p class="op-mini"><?= nu_icon('lock-fill') ?> Release = captured and paid out. Refund = hold voided, client pays nothing. Split = contractor receives the share (commission applies to it), the rest returns to the client.</p>
    </section>

<?php elseif ($view === 'payouts'): ?>
    <section class="op-card" id="payouts">
      <h2 class="op-h2">Payouts <small>Approved jobs waiting to be paid to the contractor</small></h2>
      <div class="op-tblwrap">
      <table class="op-tbl mk-tbl">
        <thead><tr><th>Job</th><th>Contractor</th><th>Captured</th><th>Commission</th><th>Payout</th><th>Mark paid</th></tr></thead>
        <tbody>
<?php foreach ($payouts as $p): ?>
          <tr><td><a href="<?= nu_e(nu_url('public/track.php')) ?>?id=<?= (int) $p['task_id'] ?>"><?= nu_e($p['title']) ?></a></td><td><?= nu_e($p['contractor_name']) ?></td><td>$<?= number_format((float) $p['captured_amount'], 2) ?></td><td>$<?= number_format((float) $p['commission'], 2) ?></td><td><b>$<?= number_format((float) $p['payout_amount'], 2) ?></b></td>
            <td><?= mk_form('payout_paid', ['task_id' => $p['task_id']], 'class="mk-inline"', 'operations.php?view=payouts') ?><input class="input" name="ref" maxlength="100" placeholder="Reference" aria-label="Payout reference"><button class="btn btn-outline btn-sm" type="submit">Paid</button></form></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
<?php if (!$payouts): nu_empty('wallet2', 'No payouts pending', 'Approved jobs paid through a live processor queue here until you mark them paid. Sandbox jobs pay out instantly.'); endif; ?>
      <p class="op-mini"><?= nu_icon('info-circle') ?> Automatic contractor payouts (Stripe Connect) are not connected yet. Until then, pay contractors manually and record the reference here.</p>
    </section>

<?php elseif ($view === 'snow'): ?>
    <section class="op-card" id="snow">
      <h2 class="op-h2">Record a snow event <small>Creates one visit per confirmed seasonal plan</small></h2>
      <?= mk_form('snow_event', [], 'class="mk-form mk-form--row"', 'operations.php?view=snow') ?>
        <label>Event name or date<input class="input" name="event_key" maxlength="60" placeholder="2026-11-03 AM" required></label>
        <label>Snowfall (cm)<input class="input" name="snowfall_cm" type="number" step="0.1" min="0" max="200" required></label>
        <label>Area (optional)<input class="input" name="area" maxlength="80" placeholder="Edmonton"></label>
        <button class="btn btn-primary" type="submit">Create visits</button>
      </form>
      <p class="op-mini"><?= nu_icon('info-circle') ?> Below <?= (int) MK_SEASONAL_TRIGGER_CM ?> cm no visits are created. Each event can only be recorded once.</p>
<?php if ($events): ?>
      <table class="op-tbl mk-tbl"><thead><tr><th>Event</th><th>Snowfall</th><th>Area</th><th>Visits</th><th>Recorded</th></tr></thead><tbody>
<?php foreach ($events as $e): ?><tr><td><?= nu_e($e['event_key']) ?></td><td><?= nu_e($e['snowfall_cm']) ?> cm</td><td><?= nu_e($e['area']) ?></td><td><?= (int) $e['visits_created'] ?></td><td><?= nu_e(mk_dt($e['created_at'])) ?></td></tr><?php endforeach; ?>
      </tbody></table>
<?php endif; ?>
    </section>

<?php else: ?>
    <section class="op-card" id="audit">
      <h2 class="op-h2">Audit log <small>Who changed what. Entries cannot be edited here.</small></h2>
      <form class="mk-form mk-form--row" method="get"><input type="hidden" name="view" value="audit"><label class="op-search"><?= nu_icon('search') ?><span class="sr-only">Search the log</span><input class="input" type="search" name="q" value="<?= nu_e($aq) ?>" placeholder="Action, detail or task id"></label><button class="btn btn-outline btn-sm" type="submit">Search</button></form>
      <div class="op-tblwrap">
      <table class="op-tbl mk-tbl">
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Item</th><th>Detail</th></tr></thead>
        <tbody>
<?php foreach ($audit as $a): ?>
          <tr><td><?= nu_e(mk_ago($a['age_s'])) ?><small class="op-sm"><?= nu_e(mk_dt($a['created_at'], 'M j, H:i')) ?></small></td><td><?= nu_e($a['actor_name'] ?: $a['actor_role']) ?><small class="op-sm"><?= nu_e($a['actor_role']) ?></small></td><td><code><?= nu_e($a['action']) ?></code></td><td><?= nu_e($a['entity']) ?><?= $a['entity_id'] ? ' #' . (int) $a['entity_id'] : '' ?></td><td><?= nu_e($a['detail']) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
<?php if (!$audit): nu_empty('journal-text', 'No entries', 'Actions on jobs, payments, vetting and disputes are recorded here.'); endif; ?>
    </section>
<?php endif; ?>
  </main>
</div>
<?php include_once("../includes/footer.php"); ?>
