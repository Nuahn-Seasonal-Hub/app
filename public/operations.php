<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/operations.php: Operations console for the independent-contractor marketplace.
// ?view=overview (KPIs, snapshots, weather, payments) | open (open jobs) | vetting (identity, insurance, licence and
// mechanic certification review) | disputes (held funds, evidence, resolution). Dispatch is replaced by vetting and
// disputes: contractors choose their own jobs, so operations reviews who may bid and what happens when work is contested.
// UI ONLY: SAMPLE DATA from fixtures/ops.php and fixtures/marketplace.php. Buttons only change the page locally.
// Sir Paul: see docs/UI-DATA-HOOKUP.md sections 26 to 33 (vetting and disputes endpoints).
require_once("../config/init.php");
requireLogin(['manager', 'admin', 'superadmin']);

$o = nu_fixture('ops'); $m = nu_fixture('marketplace'); $services = nu_fixture('services');
$view = (string) ($_GET['view'] ?? 'overview');
if (!in_array($view, ['overview', 'open', 'vetting', 'disputes'], true)) $view = 'overview';
$nav = nu_ops_nav();
$titles = ['overview' => 'Operations overview', 'open' => 'Open jobs', 'vetting' => 'Vetting queue', 'disputes' => 'Disputes'];
$chk = ['identity' => 'Identity', 'insurance' => 'Insurance', 'licence' => 'Licence', 'cert' => 'Mechanic certification'];
$chkL = ['ok' => ['Verified', 'success', 'check-circle-fill'], 'review' => ['Needs review', 'warning', 'hourglass-split'], 'missing' => ['Missing', 'danger', 'exclamation-circle'], 'na' => ['Not required', '', 'dash-circle']];
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, $view, 'Operations') ?>
  <main class="console__main op" id="ops">
    <header class="op-head">
      <div><h1><?= nu_e($titles[$view]) ?></h1><?php if ($view === 'overview'): ?><p class="op-sub">Contractors pick their own jobs. Operations keeps the marketplace safe: who may bid, and what happens when work is contested.</p><?php endif; ?></div>
      <div class="op-sel">
        <label class="op-pick"><?= nu_icon('geo-alt-fill') ?><span class="sr-only">Area</span><select><?php foreach ($o['areas'] as $a): ?><option><?= nu_e($a) ?></option><?php endforeach; ?></select></label>
        <label class="op-pick"><?= nu_icon('calendar3') ?><span class="sr-only">Date</span><select><option>Today</option><option>Tomorrow</option><option>This week</option></select></label>
      </div>
    </header>
    <nav class="op-tabs" aria-label="Operations views">
<?php foreach ($titles as $k => $l): ?>
      <a href="?view=<?= $k ?>"<?= $k === $view ? ' aria-current="page"' : '' ?>><?= nu_e($l) ?><?php if ($k === 'open'): ?> <b><?= count($m['open_jobs']) ?></b><?php elseif ($k === 'vetting'): ?> <b><?= count($m['vetting']) ?></b><?php elseif ($k === 'disputes'): ?> <b><?= count($m['disputes']) ?></b><?php endif; ?></a>
<?php endforeach; ?>
    </nav>

<?php if ($view === 'overview'): ?>
    <div class="op-kpis">
<?php foreach ($o['kpis'] as $k): ?>
      <div class="op-kpi op-kpi--<?= $k[3] ?>"><span class="op-kpi__ic"><?= nu_icon($k[0]) ?></span><span><b><?= (int) $k[1] ?></b><?= nu_e($k[2]) ?></span></div>
<?php endforeach; ?>
    </div>
    <div class="op-row">
      <section class="op-card">
        <h2 class="op-h2">Open jobs <a class="op-more" href="?view=open">View all</a></h2>
        <ul class="op-mini-list"><?php foreach (array_slice($m['open_jobs'], 0, 3) as $j): ?><li><span><b><?= nu_e($j['title']) ?></b><small><?= nu_e($j['area']) ?> · <?= (int) $j['bids'] ?> bids · <?= nu_e($j['posted']) ?></small></span><?= nu_booking_chip('open_for_bids', false) ?></li><?php endforeach; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Vetting queue <a class="op-more" href="?view=vetting">Review</a></h2>
        <ul class="op-mini-list"><?php foreach (array_slice($m['vetting'], 0, 3) as $a): ?><li><span><b><?= nu_e($a['name']) ?></b><small><?= nu_e($a['trade']) ?> · <?= nu_e($a['submitted']) ?></small></span><span class="chip chip--warning">In review</span></li><?php endforeach; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Disputes <a class="op-more" href="?view=disputes">Open</a></h2>
        <ul class="op-mini-list"><?php foreach ($m['disputes'] as $d): ?><li><span><b><?= nu_e($d['id']) ?> · <?= nu_e($d['reason']) ?></b><small>$<?= number_format($d['held'], 2) ?> held · <?= nu_e($d['opened']) ?></small></span><?= nu_payment_chip('disputed') ?></li><?php endforeach; ?></ul>
      </section>
    </div>
    <div class="op-row">
      <section class="op-card" id="weather">
        <h2 class="op-h2">Weather</h2>
        <div class="op-wx"><?= nu_icon('cloud-snow') ?><div><b><?= nu_e($o['weather']['title']) ?></b><span><?= nu_e($o['weather']['text']) ?></span></div></div>
        <div class="op-acts"><button class="btn btn-primary btn-sm" type="button" data-ui="Notify nearby contractors about the snow event (preview, nothing sent)">Notify contractors</button><button class="btn btn-outline btn-sm" type="button" data-ui="Pause new seasonal-plan triggers for this area (preview)">Pause triggers</button></div>
        <p class="op-mini"><?= nu_icon('info-circle') ?> If forecasts fail, show “Weather unavailable” and keep the marketplace running.</p>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Contractors online</h2>
        <ul class="op-teams"><?php foreach ($o['contractors_online'] as $t): ?><li><?= nu_icon('person-check') ?><span><?= nu_e($t[0]) ?></span><?= nu_booking_chip($t[1]) ?></li><?php endforeach; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Service settings</h2>
        <ul class="op-svcs"><?php foreach (nu_services_by_status('live') as $k => $s): ?><li><a href="<?= nu_e(nu_url('public/service_settings.php') . '?s=' . $k) ?>"><?= nu_icon($s['icon']) ?><span><?= nu_e($s['name']) ?></span><?= nu_icon('chevron-right') ?></a></li><?php endforeach; ?></ul>
        <p class="op-mini"><?= nu_icon('gear-fill') ?> Price guidance, boost tiers and checklists for each live service.</p>
      </section>
    </div>
    <section class="op-card op-pay" id="payments">
      <h2 class="op-h2">Payments <small>Separate from job status</small></h2>
      <div class="op-paylist"><?php foreach ($o['payments'] as $p): ?><span><?= nu_payment_chip($p[0]) ?><b><?= (int) $p[1] ?></b></span><?php endforeach; ?></div>
      <p class="op-mini"><?= nu_icon('shield-lock') ?> Card entry is handled by a secure processor: Nuahn only stores a token. Retries use idempotency keys, so a client is never charged twice. Commission sample: <?= (int) $m['commission_pct'] ?>%.</p>
    </section>

<?php elseif ($view === 'open'): ?>
    <section class="op-card op-queue" id="queue">
      <div class="op-queue__top">
        <h2 class="op-h2">Open jobs <small>Contractors accept or counter-bid; operations only watches</small></h2>
        <div class="op-filters" data-filters>
          <label><span class="sr-only">Service</span><select class="input" data-f="svc"><option value="">All services</option><?php foreach (nu_services_by_status('live') as $k => $s): ?><option value="<?= nu_e($k) ?>"><?= nu_e($s['name']) ?></option><?php endforeach; ?></select></label>
          <label><span class="sr-only">Status</span><select class="input" data-f="status"><option value="">All types</option><option value="no_bids">No bids yet</option><option value="boosted">Boosted</option><option value="roadside">Roadside</option><option value="quote">Quote needed</option></select></label>
          <label class="op-search"><?= nu_icon('search') ?><span class="sr-only">Search jobs</span><input class="input" type="search" placeholder="Search jobs…" data-f="q"></label>
        </div>
      </div>
      <div class="op-tblwrap">
      <table class="op-tbl" data-tbl>
        <thead><tr><th>Task</th><th>Service</th><th>Area</th><th>Budget</th><th>Bids</th><th>Posted</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($m['open_jobs'] as $j): $tags = trim(((int) $j['bids'] === 0 ? 'no_bids ' : '') . (!empty($j['boost']) ? 'boosted ' : '') . (!empty($j['roadside']) ? 'roadside ' : '') . (!empty($j['quote']) ? 'quote' : '')); ?>
          <tr data-svc="<?= nu_e($j['svc']) ?>" data-status="<?= nu_e($tags) ?>">
            <td><b><?= nu_e($j['id']) ?></b><small class="op-sm"><?= nu_e($j['title']) ?></small></td><td><?= nu_e($services[$j['svc']]['name']) ?></td><td><?= nu_e($j['area']) ?></td><td><?= $j['budget'] ? '$' . (int) $j['budget'] . (!empty($j['boost']) ? ' <span class="chip chip--blue">Boosted</span>' : '') : 'Quote' ?></td><td><?= (int) $j['bids'] ?></td><td><?= nu_e($j['posted']) ?></td>
            <td><button class="btn btn-outline btn-sm" type="button" data-ui="Nudge nearby verified contractors about <?= nu_e($j['id']) ?> (preview, nothing sent)">Nudge</button></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <p class="op-empty" data-empty hidden>No jobs match these filters.</p>
      <p class="pd-ui" data-ui-out role="status"></p>
    </section>

<?php elseif ($view === 'vetting'): ?>
    <section class="op-card" id="vetting">
      <h2 class="op-h2">Applications to review <small>Identity, insurance, licence and mechanic certification</small></h2>
      <div class="vet-list">
<?php foreach ($m['vetting'] as $a): $all = !array_diff(array_values($a['checks']), ['ok', 'na']); ?>
        <article class="vet" data-vet="<?= nu_e($a['id']) ?>">
          <header><div><b><?= nu_e($a['name']) ?></b><span><?= nu_e($a['id']) ?> · <?= nu_e($a['trade']) ?> · <?= nu_e($a['submitted']) ?></span></div><span class="chip chip--warning" data-vet-chip>In review</span></header>
          <ul class="vet__checks">
<?php foreach ($chk as $ck => $cl): [$l, $t, $ic] = $chkL[$a['checks'][$ck]]; ?>
            <li class="vet__c"><span><?= nu_e($cl) ?></span><span class="chip<?= $t ? ' chip--' . $t : '' ?>"><?= nu_icon($ic) ?><?= nu_e($l) ?></span><button class="linkbtn" type="button" data-ui="Open the <?= nu_e(strtolower($cl)) ?> document for <?= nu_e($a['name']) ?> (preview)">View</button></li>
<?php endforeach; ?>
          </ul>
          <p class="vet__note"><?= nu_icon('chat-left-text') ?><?= nu_e($a['note']) ?></p>
          <div class="op-acts">
            <button class="btn btn-primary btn-sm" type="button" data-vet-act="Approved"<?= $all ? '' : ' disabled title="Resolve every check first"' ?>>Approve</button>
            <button class="btn btn-outline btn-sm" type="button" data-vet-act="Changes requested">Request changes</button>
            <button class="btn btn-ghost btn-sm" type="button" data-vet-act="Rejected">Reject</button>
          </div>
        </article>
<?php endforeach; ?>
      </div>
      <p class="op-mini"><?= nu_icon('shield-check') ?> Only approved contractors see open jobs. Badges on a contractor’s public profile come from these checks.</p>
      <p class="pd-ui" data-ui-out role="status"></p>
    </section>

<?php else: ?>
    <section class="op-card" id="disputes">
      <h2 class="op-h2">Disputes <small>Funds stay held until a decision</small></h2>
      <div class="vet-list">
<?php foreach ($m['disputes'] as $d): $c = nu_contractor($d['contractor']); ?>
        <article class="vet" data-vet="<?= nu_e($d['id']) ?>">
          <header><div><b><?= nu_e($d['id']) ?> · <?= nu_e($d['reason']) ?></b><span><?= nu_e($d['service']) ?> #<?= nu_e($d['job']) ?> · opened <?= nu_e($d['opened']) ?></span></div><span data-vet-chip><?= nu_payment_chip('disputed') ?></span></header>
          <dl class="vet__dl">
            <div><dt>Client</dt><dd><?= nu_e($d['client']) ?></dd></div>
            <div><dt>Contractor</dt><dd><a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($c['id']) ?>"><?= nu_e($c['name']) ?></a></dd></div>
            <div><dt>Held</dt><dd>$<?= number_format($d['held'], 2) ?></dd></div>
            <div><dt>Evidence</dt><dd>Before and after photos · checklist · chat history</dd></div>
          </dl>
          <div class="op-acts">
            <button class="btn btn-primary btn-sm" type="button" data-vet-act="Released to contractor">Release to contractor</button>
            <button class="btn btn-outline btn-sm" type="button" data-vet-act="Refunded to client">Refund client</button>
            <button class="btn btn-outline btn-sm" type="button" data-vet-act="Partial refund issued">Partial refund</button>
            <button class="btn btn-ghost btn-sm" type="button" data-ui="Message both parties asking for more information (preview)">Ask for info</button>
          </div>
        </article>
<?php endforeach; ?>
      </div>
      <p class="op-mini"><?= nu_icon('lock-fill') ?> A decision moves the held amount: release (captured and paid out), refund, or split. Both sides are notified and the case is closed.</p>
      <p class="pd-ui" data-ui-out role="status"></p>
    </section>
<?php endif; ?>
    <p class="ch-demo"><?= nu_sample_tag('Sample operations data') ?></p>
  </main>
</div>
<script>
(function () {
  var out = document.querySelector('[data-ui-out]');
  function say(t) { if (out) out.textContent = t; }
  document.querySelectorAll('[data-ui]').forEach(function (b) { b.addEventListener('click', function () { say(b.dataset.ui); }); });
  document.querySelectorAll('[data-vet-act]').forEach(function (b) { b.addEventListener('click', function () {
    var card = b.closest('[data-vet]'), chip = card.querySelector('[data-vet-chip]');
    chip.className = 'chip chip--' + (/Reject|Refund/.test(b.dataset.vetAct) ? 'danger' : 'success'); chip.textContent = b.dataset.vetAct;
    card.querySelectorAll('[data-vet-act]').forEach(function (x) { x.disabled = true; });
    say(card.dataset.vet + ': ' + b.dataset.vetAct + ' (preview, nothing was saved).');
  }); });
  var f = document.querySelector('[data-filters]'), rows = document.querySelectorAll('[data-tbl] tbody tr');
  if (!f) return;
  function apply() {
    var svc = f.querySelector('[data-f=svc]').value, st = f.querySelector('[data-f=status]').value, q = f.querySelector('[data-f=q]').value.toLowerCase(), n = 0;
    rows.forEach(function (r) { var ok = (!svc || r.dataset.svc === svc) && (!st || r.dataset.status.split(' ').indexOf(st) > -1) && (!q || r.textContent.toLowerCase().indexOf(q) > -1); r.hidden = !ok; if (ok) n++; });
    document.querySelector('[data-empty]').hidden = n > 0;
  }
  f.addEventListener('input', apply); f.addEventListener('change', apply);
})();
</script>
<?php include_once("../includes/footer.php"); ?>
