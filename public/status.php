<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/status.php: booking states, timing and exception rules (doc1 p13, doc2 p21-23) as a visual reference.
// One status vocabulary (nu_booking_status / nu_payment_chip in includes/ui.php) drives every customer, provider and
// admin panel. Each rule links to the screen state that demonstrates it, for review and acceptance testing.
// UI ONLY: no data; this page documents states. Sir Paul: the backend must emit exactly these state keys.
require_once("../config/init.php");

$u = function ($p) { return nu_url('public/' . $p); };
$life = ['requested', 'open_for_bids', 'funds_held', 'on_the_way', 'arrived', 'in_progress', 'pending_approval', 'approved'];
$exc = ['cancelled', 'delayed', 'change_requested', 'disputed', 'weather_review', 'no_provider'];
$pay = ['unpaid', 'authorized', 'paid', 'failed', 'partially_refunded', 'refunded', 'disputed'];
$rules = [
  ['Requested / open for bids', ['requested', 'open_for_bids'], 'The task, the target budget and the offers coming in; a boost raises pickup speed', 'Notify nearby approved contractors; show no-offers and no-provider fallbacks; keep the payment Unpaid', [['Post a task', 'post_task.php'], ['Review offers', 'offers.php?state=many'], ['No offers yet', 'offers.php?state=none'], ['Roadside request', 'mechanic_request.php?path=roadside&svc=flat-tire&state=broadcast']]],
  ['Confirmed with funds held', ['confirmed'], 'Agreed contractor, price and window; “Funds held securely until you approve the work”', 'Authorize (hold) the payment with the secure processor; show Authorized in the payment chip', [['Accept an offer', 'offers.php?state=many'], ['Quote approved', 'quote.php?state=approved']]],
  ['Change requested', ['change_requested'], 'Old window retained; proposed new window pending', 'Ask the contractor to confirm or decline without losing the old slot', [['Change appointment', 'change_appointment.php'], ['Snow: pending', 'snow_event.php?state=pending'], ['Declined', 'snow_event.php?state=declined']]],
  ['On the way / arrived', ['on_the_way', 'arrived'], 'ETA freshness, trip map, message; arrival update', 'Active-trip location sharing only; contractor confirms arrival', [['Live tracking', 'track.php'], ['Stale location', 'track.php?state=stale']]],
  ['In progress / revised quote', ['in_progress'], 'Work update; an on-site revised quote needs your approval before extra work', 'Pause the extra work until the client approves the amended amount', [['Revised quote', 'quote.php?state=revised'], ['Contractor job', 'provider_dashboard.php']]],
  ['Completed, pending approval / approved', ['pending_approval', 'approved'], 'Before and after photos, checklist; Approve and release payment or Report a problem', 'Require at least one before and one after photo; capture and pay out only after approval', [['Review and approve', 'approve.php?id=SNOW-1035'], ['Completion report', 'track.php?state=completed']]],
  ['Disputed', ['disputed'], 'Funds stay held; the case and the decision are visible', 'Operations reviews evidence, then releases, refunds or splits the held amount', [['Report a problem', 'approve.php?id=SNOW-1035&state=dispute'], ['Disputes queue', 'operations.php?view=disputes']]],
];
$scen = [
  ['A guest prices a snow task', 'Address, scope and a price range before any sign-in; the sign-in prompt appears only at Post task; the draft survives sign-in.', [['Post a task (guest)', 'post_task.php']]],
  ['The budget is too low', 'Boost your bid shows the effect on expected pickup time; offers still arrive at the contractor’s own price.', [['Post a task', 'post_task.php'], ['Boosted offers', 'offers.php?state=boosted']]],
  ['Roadside breakdown', 'Proximity broadcast to nearby approved technicians; no scheduling; the first to accept is shown with an ETA.', [['Roadside request', 'mechanic_request.php?path=roadside&svc=flat-tire&state=broadcast'], ['Mobile mechanics', 'mechanics.php']]],
  ['Mechanic finds more work on site', 'A revised quote with the new amount; the client approves or declines before extra work; funds held change only after approval.', [['Revised quote', 'quote.php?state=revised']]],
  ['Contractor must prove the work', 'The completion report cannot be submitted until a before and an after photo are uploaded.', [['Contractor job', 'provider_dashboard.php'], ['Review and approve', 'approve.php?id=SNOW-1035']]],
  ['Work is contested', 'The client reports a problem; payment shows Disputed; operations resolves it from the disputes queue.', [['Report a problem', 'approve.php?id=SNOW-1035&state=dispute'], ['Disputes', 'operations.php?view=disputes']]],
  ['A new contractor joins', 'Identity, insurance, licence and mechanic certification are reviewed before they can see open jobs.', [['Vetting queue', 'operations.php?view=vetting'], ['Contractor profile', 'contractor.php?id=c-1001']]],
];
$checks = [
  ['A guest can explore scope and a price range; sign-in is asked for only when posting.', 'post_task.php'], ['Only the two live services take tasks; locked services show Coming soon.', 'index.php#services'],
  ['Show price and scope before confirmation; extra work needs approval.', 'quote.php'], ['Contractor status changes appear in the client timeline and notifications.', 'activity.php?tab=notifications'],
  ['Weather rescheduling records a reason and does not duplicate jobs.', 'snow_event.php?state=declined'], ['Completion needs before and after photos; an issue opens a disputes case.', 'approve.php?id=SNOW-1035'],
  ['Payment retries cannot create duplicate charges; failed payments stay visible.', 'operations.php#payments'], ['Each role can access only its permitted records.', 'operations.php'],
  ['A new service can be configured without rebuilding the common workflow.', 'service_settings.php?new=1'],
];
include_once("../includes/header.php");
?>
<main class="st" id="states">
  <header class="st-head">
    <h1>Job states &amp; rules</h1>
    <p>Clients, contractors and admins see the same job state, and payment is shown separately. Each rule links to the screen that shows it.</p>
  </header>

  <section class="op-card st-sec">
    <h2 class="op-h2">Job lifecycle</h2>
    <ol class="st-life"><?php foreach ($life as $i => $s): ?><li><span class="st-n"><?= $i + 1 ?></span><?= nu_booking_chip($s) ?></li><?php endforeach; ?></ol>
    <div class="st-cols">
      <div><h3 class="st-h3">Exceptions</h3><div class="st-chips"><?php foreach ($exc as $s) echo nu_booking_chip($s); ?></div></div>
      <div><h3 class="st-h3">Payment (separate state)</h3><div class="st-chips"><?php foreach ($pay as $s) echo nu_payment_chip($s); ?></div>
        <p class="op-mini">A job with funds held is not paid out. Payment is captured and paid out only after the client approves the work; reminders, job creation and confirmed reschedules never trigger duplicate payment attempts.</p></div>
    </div>
  </section>

  <section class="op-card st-sec">
    <h2 class="op-h2">Status, timing and exception rules</h2>
    <div class="st-rules">
<?php foreach ($rules as $r): ?>
      <article class="st-rule">
        <h3><?= nu_e($r[0]) ?> <span><?php foreach ($r[1] as $c) echo nu_booking_chip($c); ?></span></h3>
        <dl><div><dt>Customer sees</dt><dd><?= nu_e($r[2]) ?></dd></div><div><dt>System must do</dt><dd><?= nu_e($r[3]) ?></dd></div></dl>
        <p class="st-links"><?php foreach ($r[4] as $l): ?><a href="<?= nu_e($u($l[1])) ?>"><?= nu_e($l[0]) ?> <?= nu_icon('arrow-right') ?></a><?php endforeach; ?></p>
      </article>
<?php endforeach; ?>
    </div>
  </section>

  <section class="op-card st-sec">
    <h2 class="op-h2">Acceptance scenarios</h2>
    <div class="st-tbl">
<?php foreach ($scen as $s): ?>
      <div class="st-row"><b><?= nu_e($s[0]) ?></b><span><?= nu_e($s[1]) ?></span><p class="st-links"><?php foreach ($s[2] as $l): ?><a href="<?= nu_e($u($l[1])) ?>"><?= nu_e($l[0]) ?> <?= nu_icon('arrow-right') ?></a><?php endforeach; ?></p></div>
<?php endforeach; ?>
    </div>
  </section>

  <section class="op-card st-sec">
    <h2 class="op-h2">Acceptance checks</h2>
    <ol class="st-checks"><?php foreach ($checks as $c): ?><li><span><?= nu_e($c[0]) ?></span><a href="<?= nu_e($u($c[1])) ?>">See screen <?= nu_icon('arrow-right') ?></a></li><?php endforeach; ?></ol>
    <p class="op-mini"><?= nu_icon('info-circle') ?> Screens show the intended UI states with sample data. The behaviour behind each check is built separately.</p>
  </section>
</main>
<?php include_once("../includes/footer.php"); ?>
