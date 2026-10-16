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
$life = ['requested', 'awaiting_quote', 'confirmed', 'assigned', 'on_the_way', 'arrived', 'in_progress', 'paused', 'completed', 'closed'];
$exc = ['cancelled', 'delayed', 'reschedule_pending', 'disputed', 'weather_review', 'no_provider'];
$pay = ['unpaid', 'authorized', 'paid', 'failed', 'partially_refunded', 'refunded'];
$rules = [
  ['Requested / quote pending', ['requested', 'quote_pending'], 'Request received; next action; no promised repair slot', 'Confirm scope, availability and quote; show no-provider fallback', [['Quote requested', 'mechanic_request.php?step=3'], ['No provider', 'mechanic_request.php?svc=flat-tire&state=no_provider']]],
  ['Confirmed / assigned', ['confirmed', 'assigned'], 'Agreed scope, price, window; provider when assigned', 'Reserve team capacity and required equipment / parts', [['Booking confirmed', 'book.php?service=car-wash'], ['Quote approved', 'quote.php?state=approved']]],
  ['Change requested', ['change_requested'], 'Old window retained; proposed new window pending', 'Re-check capacity / route; confirm or decline without losing old slot', [['Change appointment', 'change_appointment.php'], ['Snow: pending', 'snow_event.php?state=pending'], ['Declined', 'snow_event.php?state=declined']]],
  ['On the way / arrived', ['on_the_way', 'arrived'], 'ETA freshness, trip map, message; arrival update', 'Active-trip location sharing; route to dispatch for late changes', [['Live tracking', 'track.php'], ['Stale location', 'track.php?state=stale']]],
  ['In progress / paused', ['in_progress', 'paused'], 'Work update, issue or extra quote if needed', 'Timestamp work; pause affected extra work until approval', [['Extra work found', 'quote.php?state=revised'], ['Provider job', 'provider_dashboard.php']]],
  ['Completed / disputed', ['completed', 'disputed'], 'Evidence and invoice; issue / case status', 'Require successful uploads; support reviews disputes; preserve audit trail', [['Completion report', 'track.php?state=completed'], ['Activity & issue', 'activity.php#feedback']]],
];
$scen = [
  ['Basic car wash with provider water + customer power', 'Correct equipment match; utility selection visible to both parties; charges reviewed before booking.', [['Car wash page', 'service.php?s=car-wash'], ['Equipment queue', 'operations.php?queue=equipment#queue']]],
  ['Premium monthly reminder', 'Attributed provider reminder; confirm / change / skip actions; no visit or charge until authorized.', [['Care plans', 'care_plans.php'], ['Plans', 'plans.php']]],
  ['Tuesday mechanic moved to Wednesday', 'Available new slot reserved; old slot released only after success; both users notified; Activity records old and new.', [['Change appointment', 'change_appointment.php?id=MECH-1042']]],
  ['Snow customer does not want to be first', 'Later-visit request enters dispatch; pending state visible; route and customer window update only after confirmation.', [['Snow event', 'snow_event.php'], ['Reschedule queue', 'operations.php?queue=reschedule#queue']]],
  ['Location / weather / connectivity failure', 'Last-update / unavailable / pending-sync states are honest; booking remains actionable; no fabricated activity.', [['Stale location', 'track.php?state=stale'], ['Weather unavailable', 'snow_event.php?weather=unavailable'], ['Pending sync', 'provider_dashboard.php']]],
  ['Work finished or extra work found', 'Successful evidence upload before completion; extra quote needs approval; invoice and support case remain accessible.', [['Provider upload', 'provider_dashboard.php'], ['Revised quote', 'quote.php?state=revised'], ['Activity', 'activity.php']]],
];
$checks = [
  ['Book each service through the same flow with the correct questions.', 'service.php?s=garbage-bin-cleaning'], ['Reject unsupported addresses and prevent double-booking team capacity.', 'book.php?service=snow-removal'],
  ['Show price and scope before confirmation; require approval for extra work.', 'quote.php'], ['Provider status changes appear in the customer timeline and notifications.', 'activity.php?tab=notifications'],
  ['Weather rescheduling records a reason and does not duplicate jobs.', 'snow_event.php?state=declined'], ['Completion shows a checklist and photos; an issue opens an admin case.', 'activity.php#feedback'],
  ['Payment retries cannot create duplicate charges; failed payments stay visible.', 'operations.php#payments'], ['Each role can access only its permitted records.', 'operations.php'],
  ['A new service can be configured without rebuilding the common workflow.', 'service_settings.php?new=1'],
];
include_once("../includes/header.php");
?>
<main class="st" id="states">
  <header class="st-head">
    <h1>Booking states &amp; rules</h1>
    <p>Customers, providers and admins see the same booking state. Each rule links to the screen that shows it.</p>
  </header>

  <section class="op-card st-sec">
    <h2 class="op-h2">Job lifecycle</h2>
    <ol class="st-life"><?php foreach ($life as $i => $s): ?><li><span class="st-n"><?= $i + 1 ?></span><?= nu_booking_chip($s) ?></li><?php endforeach; ?></ol>
    <div class="st-cols">
      <div><h3 class="st-h3">Exceptions</h3><div class="st-chips"><?php foreach ($exc as $s) echo nu_booking_chip($s); ?></div></div>
      <div><h3 class="st-h3">Payment (separate state)</h3><div class="st-chips"><?php foreach ($pay as $s) echo nu_payment_chip($s); ?></div>
        <p class="op-mini">A paid job is not necessarily completed. Plan fees and visit fees are shown separately; reminders, job creation and confirmed reschedules never trigger duplicate payment attempts.</p></div>
    </div>
  </section>

  <section class="op-card st-sec">
    <h2 class="op-h2">Status, timing and exception rules</h2>
    <div class="st-rules">
<?php foreach ($rules as $r): ?>
      <article class="st-rule">
        <h3><?= nu_e($r[0]) ?> <span><?php foreach ($r[1] as $c) echo nu_booking_chip($c); ?></span></h3>
        <dl><div><dt>Customer sees</dt><dd><?= nu_e($r[2]) ?></dd></div><div><dt>System / team must do</dt><dd><?= nu_e($r[3]) ?></dd></div></dl>
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
