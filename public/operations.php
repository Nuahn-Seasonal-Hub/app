<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/operations.php: "Operations overview" (doc1 p5, doc2 p20). Admin dispatch console: KPIs, dispatch queue
// plus the missing queues (reschedule requests, snow events, equipment matching, Premium reminders, customer issues),
// filters, assign-team panel (qualification, coverage, equipment, travel time, parts, capacity), weather review,
// team availability, service settings and payment states (separate from job status).
// UI ONLY: SAMPLE DATA from fixtures/ops.php. Filters / assign only change the page locally.
// Sir Paul: see fixtures/ops.php header. Confirmed changes must update dispatch, route, customer window and
// provider schedule together (doc2 p20, p22).
require_once("../config/init.php");
requireLogin(['manager', 'admin', 'superadmin']);

$o = nu_fixture('ops'); $services = nu_fixture('services');
$q = (string) ($_GET['queue'] ?? 'dispatch');
if (!isset($o['queues'][$q])) $q = 'dispatch';
$u = function ($p) { return nu_url('public/' . $p); };
$nav = [
  ['overview', 'Overview', $u('operations.php'), 'grid-fill'], ['bookings', 'Bookings', $u('jobs.php'), 'calendar3'],
  ['dispatch', 'Dispatch', '#queue', 'truck'], ['teams', 'Teams', '#teams', 'people-fill'],
  ['quotes', 'Quotes', '?queue=dispatch&status=quote_pending#queue', 'file-text'], ['payments', 'Payments', '#payments', 'credit-card'],
  ['weather', 'Weather', '#weather', 'cloud'], ['settings', 'Service Settings', $u('service_settings.php'), 'gear-fill'],
  ['support', 'Support', '?queue=issues#queue', 'question-circle'],
];
$statusF = (string) ($_GET['status'] ?? '');
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav($nav, 'overview', 'Operations') ?>
  <main class="console__main op" id="ops">
    <header class="op-head">
      <h1>Operations overview</h1>
      <div class="op-sel">
        <label class="op-pick"><?= nu_icon('geo-alt-fill') ?><span class="sr-only">Area</span><select><?php foreach ($o['areas'] as $a): ?><option><?= nu_e($a) ?></option><?php endforeach; ?></select></label>
        <label class="op-pick"><?= nu_icon('calendar3') ?><span class="sr-only">Date</span><select><option>Today</option><option>Tomorrow</option><option>This week</option></select></label>
      </div>
    </header>
    <div class="op-kpis">
<?php foreach ($o['kpis'] as $k): ?>
      <div class="op-kpi op-kpi--<?= $k[3] ?>"><span class="op-kpi__ic"><?= nu_icon($k[0]) ?></span><span><b><?= (int) $k[1] ?></b><?= nu_e($k[2]) ?></span></div>
<?php endforeach; ?>
    </div>

    <section class="op-card op-queue" id="queue">
      <div class="op-queue__top">
        <h2 class="op-h2"><?= nu_e($q === 'dispatch' ? 'Dispatch queue' : $o['queues'][$q]['label']) ?></h2>
        <div class="op-filters" data-filters>
          <label><span class="sr-only">Service</span><select class="input" data-f="svc"><option value="">All services</option><?php foreach ($services as $s): ?><option><?= nu_e($s['name']) ?></option><?php endforeach; ?></select></label>
          <label><span class="sr-only">Area</span><select class="input"><option>All areas</option><?php foreach ($o['areas'] as $a): ?><option><?= nu_e($a) ?></option><?php endforeach; ?></select></label>
          <label><span class="sr-only">Status</span><select class="input" data-f="status"><option value="">All statuses</option><?php foreach (['in_progress', 'unassigned', 'quote_pending', 'weather_review', 'change_requested', 'confirmed', 'disputed'] as $s): ?><option value="<?= $s ?>"<?= $statusF === $s ? ' selected' : '' ?>><?= nu_e(nu_booking_status($s)[0]) ?></option><?php endforeach; ?></select></label>
          <label class="op-search"><?= nu_icon('search') ?><span class="sr-only">Search bookings</span><input class="input" type="search" placeholder="Search bookings…" data-f="q"></label>
        </div>
      </div>
      <nav class="op-tabs" aria-label="Queues">
<?php foreach ($o['queues'] as $k => $qq): ?>
        <a href="?queue=<?= $k ?>#queue"<?= $k === $q ? ' aria-current="page"' : '' ?>><?= nu_e($qq['label']) ?> <b><?= count($qq['rows']) ?></b></a>
<?php endforeach; ?>
      </nav>
      <div class="op-tblwrap">
      <table class="op-tbl" data-tbl>
        <thead><tr><th>Booking</th><th>Service</th><th><?= $q === 'dispatch' ? 'Arrival window' : 'Detail' ?></th><th>Status</th><th>Team</th><th>Action</th></tr></thead>
        <tbody>
<?php foreach ($o['queues'][$q]['rows'] as $i => $r): ?>
          <tr data-svc="<?= nu_e($r[1]) ?>" data-status="<?= nu_e($r[3]) ?>"<?= $statusF !== '' && $statusF !== $r[3] ? ' hidden' : '' ?>>
            <td><b><?= nu_e($r[0]) ?></b></td><td><?= nu_e($r[1]) ?></td><td><?= nu_e($r[2]) ?></td><td><?= nu_booking_chip($r[3]) ?></td><td><?= nu_e($r[4]) ?></td>
            <td><button class="btn btn-primary btn-sm" type="button"<?= $r[5] === 'Assign' ? ' data-assign="' . nu_e($r[0]) . '"' : ' data-ui="' . nu_e($r[5] . ' ' . $r[0] . ': detail opens here (preview)') . '"' ?>><?= nu_e($r[5]) ?></button></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <p class="op-empty" data-empty hidden>No bookings match these filters.</p>
      <div class="op-assign" data-assign-panel hidden>
        <div class="op-assign__top"><h3>Assign <span data-assign-id></span></h3><button class="linkbtn" type="button" data-assign-close>Close</button></div>
        <p class="op-mini">Suggested teams, matched on qualification, coverage, equipment, travel time, parts and actual capacity.</p>
        <ul class="op-match">
          <li><label class="radio"><input type="radio" name="team" checked><span><b>Team B</b> · 12 min away · 2 of 6 slots free</span></label><span class="op-tags"><span>Qualified</span><span>Covers area</span><span>Water tank</span></span></li>
          <li><label class="radio"><input type="radio" name="team"><span><b>Team D</b> · 25 min away · 1 slot free</span></label><span class="op-tags"><span>Qualified</span><span>Covers area</span><span class="is-miss">No tank</span></span></li>
          <li class="is-off"><label class="radio"><input type="radio" name="team" disabled><span><b>Team A</b> · at capacity</span></label><span class="op-tags"><span class="is-miss">Busy until 2 PM</span></span></li>
        </ul>
        <div class="op-acts"><button class="btn btn-primary btn-sm" type="button" data-assign-ok>Assign team</button><span class="op-mini">Customer and team are notified once; route and schedule update together.</span></div>
      </div>
      <p class="pd-ui" data-ui-out role="status"></p>
    </section>

    <div class="op-row">
      <section class="op-card" id="weather">
        <h2 class="op-h2">Weather review</h2>
        <div class="op-wx"><?= nu_icon('cloud-snow') ?><div><b><?= nu_e($o['weather']['title']) ?></b><span><?= nu_e($o['weather']['text']) ?></span></div></div>
        <div class="op-acts"><a class="btn btn-primary btn-sm" href="?queue=dispatch&amp;status=weather_review#queue">Review affected jobs</a><button class="btn btn-outline btn-sm" type="button" data-ui="Propose a new window: opens the shared Change appointment flow per booking (preview)">Propose new window</button></div>
        <p class="op-mini"><?= nu_icon('info-circle') ?> Notify customers after dispatch confirms the change. If forecasts fail, show “Weather unavailable” and keep operating.</p>
      </section>
      <section class="op-card" id="teams">
        <h2 class="op-h2">Team availability</h2>
        <ul class="op-teams"><?php foreach ($o['teams'] as $t): ?><li><?= nu_icon('people-fill') ?><span><?= nu_e($t[0]) ?></span><?= nu_booking_chip($t[1]) ?></li><?php endforeach; ?></ul>
      </section>
      <section class="op-card">
        <h2 class="op-h2">Service settings</h2>
        <ul class="op-svcs"><?php foreach ($services as $k => $s): ?><li><a href="<?= nu_e($u('service_settings.php') . '?s=' . $k) ?>"><?= nu_icon($s['icon']) ?><span><?= nu_e($s['name']) ?></span><?= nu_icon('chevron-right') ?></a></li><?php endforeach; ?></ul>
        <a class="btn btn-primary btn-sm btn-block" href="<?= nu_e($u('service_settings.php') . '?new=1') ?>"><?= nu_icon('plus-lg') ?> Add service</a>
        <p class="op-mini"><?= nu_icon('gear-fill') ?> Configure forms, price rules and checklists for each service.</p>
      </section>
    </div>
    <section class="op-card op-pay" id="payments">
      <h2 class="op-h2">Payments <small>Separate from job status</small></h2>
      <div class="op-paylist"><?php foreach ($o['payments'] as $p): ?><span><?= nu_payment_chip($p[0]) ?><b><?= (int) $p[1] ?></b></span><?php endforeach; ?></div>
      <p class="op-mini"><?= nu_icon('shield-lock') ?> Hosted, tokenised card entry. Retries use idempotency keys, so a customer is never charged twice.</p>
    </section>
    <p class="ch-demo"><?= nu_sample_tag('Sample operations data') ?></p>
  </main>
</div>
<script>
(function () {
  var out = document.querySelector('[data-ui-out]');
  document.querySelectorAll('[data-ui]').forEach(function (b) { b.addEventListener('click', function () { out.textContent = b.dataset.ui; }); });
  var panel = document.querySelector('[data-assign-panel]');
  document.querySelectorAll('[data-assign]').forEach(function (b) { b.addEventListener('click', function () { panel.hidden = false; panel.querySelector('[data-assign-id]').textContent = b.dataset.assign; panel.scrollIntoView({ block: 'nearest' }); }); });
  panel.querySelector('[data-assign-close]').addEventListener('click', function () { panel.hidden = true; });
  panel.querySelector('[data-assign-ok]').addEventListener('click', function () { panel.hidden = true; out.textContent = 'Assigned (preview). Nothing was saved.'; });
  var f = document.querySelector('[data-filters]'), rows = document.querySelectorAll('[data-tbl] tbody tr');
  function apply() {
    var svc = f.querySelector('[data-f=svc]').value, st = f.querySelector('[data-f=status]').value, q = f.querySelector('[data-f=q]').value.toLowerCase(), n = 0;
    rows.forEach(function (r) { var ok = (!svc || r.dataset.svc.indexOf(svc.split(' ')[0]) > -1) && (!st || r.dataset.status === st) && (!q || r.textContent.toLowerCase().indexOf(q) > -1); r.hidden = !ok; if (ok) n++; });
    document.querySelector('[data-empty]').hidden = n > 0;
  }
  f.addEventListener('input', apply); f.addEventListener('change', apply);
})();
</script>
<?php include_once("../includes/footer.php"); ?>
