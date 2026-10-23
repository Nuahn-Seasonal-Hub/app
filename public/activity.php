<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/activity.php: "Booking activity" (doc2 p15) + notifications with delivery state (doc2 p22).
// A complete record across bookings, with a clear split between EVENTS (auditable, from real actions: actor,
// timestamp, type) and MESSAGES (conversation, in messages.php). Completed = contractor submitted the work; it does
// not mean the customer accepted it or waived a complaint, so rating and "Report an issue" stay available.
// UI ONLY: SAMPLE DATA from fixtures/events.php. Rating / issue forms only show their UI states here.
// Sir Paul: GET /api/bookings/{id}/events, GET /api/bookings/{id}/completion, GET /api/invoices/{id},
// POST /api/bookings/{id}/rating {stars, comment}, POST /api/cases {booking_id, reason, details, photos[]},
// GET /api/notifications?status= (channel, delivery_state: pending|delivered|read|failed).
require_once("../config/init.php");
requireLogin('client');

$ev = nu_fixture('events'); $bk = $ev['booking']; $sv = nu_service($bk['service']);
$tab = ($_GET['tab'] ?? '') === 'notifications' ? 'notifications' : 'activity';
$sub = array_sum(array_column($bk['invoice'], 1)); $total = $sub + $bk['tax'];
$deliv = ['delivered' => ['Delivered', 'success'], 'read' => ['Read', 'blue'], 'pending' => ['Sending…', 'warning'], 'failed' => ['Failed · retry queued', 'danger']];
$nIcon = ['booking_confirmed' => 'calendar-check', 'contractor_accepted' => 'people', 'offer_received' => 'chat-square-text', 'funds_held' => 'shield-lock-fill', 'approval_needed' => 'check2-all', 'payout_outcome' => 'wallet-fill', 'dispute_opened' => 'flag', 'reminder_due' => 'bell', 'reschedule_requested' => 'clock-history', 'reschedule_confirmed' => 'calendar-check',
  'reschedule_declined' => 'x-circle', 'trip_started' => 'truck', 'eta_delay' => 'hourglass-split', 'weather_hold' => 'cloud-rain', 'work_started' => 'play-circle', 'quote_approval' => 'file-text',
  'completion' => 'check-circle-fill', 'payment_outcome' => 'credit-card'];
include_once("../includes/header.php");
?>
<main class="ac" id="activity">
  <div class="ac-wrap">
    <div class="ac-head">
      <div>
        <h1><?= $tab === 'activity' ? 'Booking activity / ' . nu_e($bk['title']) : 'Notifications' ?></h1>
        <p class="crumbs"><a href="<?= nu_e(nu_url('public/client_home.php')) ?>">Nuahn</a> / <a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My Bookings</a> / <b>Activity</b> / <a href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a></p>
      </div>
      <nav class="ac-tabs" aria-label="Activity views">
        <a href="?"<?= $tab === 'activity' ? ' aria-current="page"' : '' ?>><?= nu_icon('activity') ?> Booking activity</a>
        <a href="?tab=notifications"<?= $tab === 'notifications' ? ' aria-current="page"' : '' ?>><?= nu_icon('bell') ?> Notifications</a>
      </nav>
    </div>

<?php if ($tab === 'activity'): ?>
    <div class="ac-bk">
      <span class="ac-bk__ic"><?= nu_icon($sv['icon']) ?></span>
      <div><b><?= nu_e($sv['name']) ?> · <?= nu_e($bk['package']) ?></b><span><?= nu_e($bk['date']) ?> · <a href="<?= nu_e(nu_url('public/contractor.php')) ?>?id=<?= nu_e($bk['contractor']) ?>"><?= nu_e($bk['team']) ?></a> · <?= nu_e($bk['utilities']) ?></span></div>
      <div class="ac-bk__chips"><?= nu_booking_chip($bk['status']) ?><?= nu_payment_chip($bk['payment']) ?></div>
      <label class="sr-only" for="ac-switch">Booking</label>
      <select class="input ac-switch" id="ac-switch" onchange="if(this.value)location.href=this.value"><option value="">#SNOW-1031 · Snow clearing (approved)</option><option value="<?= nu_e(nu_url('public/track.php')) ?>">#NSH-1042 · Snow clearing (live)</option><option value="<?= nu_e(nu_url('public/snow_event.php')) ?>">#SNOW-1045 · Snow event</option><option value="<?= nu_e(nu_url('public/quote.php')) ?>">#MECH-1042 · Brake pads (quote)</option></select>
    </div>
    <div class="ac-jsm"><?= nu_job_machine($bk['status'] === 'approved' ? 'approved' : $bk['status']) ?></div>

    <section class="ac-box ac-tl">
      <h2>Job timeline <small>Events are recorded from real actions</small></h2>
      <ol>
<?php foreach ($bk['timeline'] as $t): ?>
        <li><span class="ac-tl__ic"><?= nu_icon($t[4]) ?></span><time><?= nu_e($t[0]) ?></time><span class="ac-tl__t"><b><?= nu_e($t[1]) ?></b><?= $t[2] !== '' ? '; ' . nu_e($t[2]) : '.' ?></span><span class="ac-tl__a"><?= nu_e($t[3]) ?></span></li>
<?php endforeach; ?>
      </ol>
    </section>

    <div class="ac-two">
      <section class="ac-box" id="evidence">
        <h2>Completion evidence</h2>
        <div class="ba ac-ba">
          <figure class="is-before"><?= nu_photo('svc-snow-removal', 'Driveway before clearing (sample photo)', ['widths' => [640, 1024], 'ratio' => 16 / 6, 'sizes' => '(max-width: 600px) 45vw, 240px']) ?><figcaption>Before <small>09:26</small></figcaption></figure>
          <figure><?= nu_photo('svc-snow-removal', 'Driveway after clearing (sample photo)', ['widths' => [640, 1024], 'ratio' => 16 / 6, 'sizes' => '(max-width: 600px) 45vw, 240px']) ?><figcaption>After <small>10:12</small></figcaption></figure>
        </div>
        <p class="ac-lab">Completion checklist</p>
        <ul class="tk-check"><?php foreach ($bk['checklist'] as $c): ?><li><?= nu_icon('check-circle-fill') ?><?= nu_e($c[0]) ?></li><?php endforeach; ?></ul>
        <p class="ac-lab">Contractor notes and work duration</p>
        <p class="ac-note"><?= nu_e($bk['notes']) ?> <span class="chip"><?= nu_icon('stopwatch') ?> <?= nu_e($bk['duration']) ?></span></p>
        <?= nu_sample_tag('Sample photos') ?>
      </section>

      <section class="ac-box" id="feedback">
        <h2>Customer actions</h2>
        <div class="ac-acts">
          <button class="btn btn-soft btn-sm" type="button" data-toggle="ac-inv" aria-expanded="false"><?= nu_icon('receipt') ?> View invoice</button>
          <button class="btn btn-soft btn-sm" type="button" data-toggle="ac-rate" aria-expanded="false"><?= nu_icon('star') ?> Rate service</button>
          <button class="btn btn-soft btn-sm" type="button" data-toggle="ac-issue" aria-expanded="false"><?= nu_icon('flag') ?> Report an issue</button>
          <a class="btn btn-soft btn-sm" href="<?= nu_e(nu_book_url($bk['service'])) ?>"><?= nu_icon('arrow-repeat') ?> Book again</a>
        </div>
        <div class="ac-panel" id="ac-inv" hidden>
          <table class="ac-inv"><?php foreach ($bk['invoice'] as $l): ?><tr><td><?= nu_e($l[0]) ?></td><td>CAD <?= number_format($l[1], 2) ?></td></tr><?php endforeach; ?>
          <tr><td>GST (5%)</td><td>CAD <?= number_format($bk['tax'], 2) ?></td></tr><tr class="is-total"><td>Total</td><td>CAD <?= number_format($total, 2) ?></td></tr></table>
          <p class="ac-pay">Payment: <?= nu_payment_chip($bk['payment']) ?> <small>Released Oct 17, 10:16 AM · card ending 4242 (sample)</small></p>
        </div>
        <form class="ac-panel" id="ac-rate" hidden data-rate>
          <fieldset class="ac-stars"><legend class="ac-lab">How was <?= nu_e($bk['team']) ?>?</legend>
<?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="stars" id="st<?= $i ?>" value="<?= $i ?>"<?= $i === 5 ? ' checked' : '' ?>><label for="st<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><?= nu_icon('star-fill') ?><span class="sr-only"><?= $i ?> stars</span></label>
<?php endfor; ?>
          </fieldset>
          <label class="sr-only" for="ac-com">Comment</label>
          <textarea class="input" id="ac-com" rows="2" placeholder="What went well? (optional)"></textarea>
          <button class="btn btn-primary btn-sm" type="submit">Submit rating</button>
          <p class="ac-ok" hidden role="status"><?= nu_icon('check-circle-fill') ?> Thanks! Your rating is shown on the contractor’s profile after review. (Preview, nothing was sent.)</p>
        </form>
        <form class="ac-panel" id="ac-issue" hidden data-issue>
          <label class="label" for="ac-reason">What went wrong?</label>
          <select class="input" id="ac-reason" required><option value="">Choose a reason</option><option>Area missed or incomplete</option><option>Damage to property or vehicle</option><option>Contractor was late or didn’t arrive</option><option>Charged incorrectly</option><option>Something else</option></select>
          <label class="label" for="ac-det">Details</label>
          <textarea class="input" id="ac-det" rows="3" placeholder="Tell us what happened"></textarea>
          <label class="ac-up"><?= nu_icon('camera') ?> Add photos (optional)<input type="file" accept="image/*" multiple class="sr-only"></label>
          <p class="fld-note"><?= nu_icon('info-circle') ?> Reporting a problem marks the job <b>Disputed</b> and keeps any held funds on hold until Nuahn reviews it. Payment may be partly or fully refunded.</p>
          <button class="btn btn-primary btn-sm" type="submit">Send to support</button>
        </form>
        <div class="ac-case" data-case>
          <span><?= nu_icon('headset') ?> Support case</span><b data-case-t>No open cases for this booking</b>
        </div>
        <p class="ac-lab">Support case status remains visible here and in Messages.</p>
      </section>
    </div>
<?php else: ?>
    <section class="ac-box ac-notes">
      <div class="ac-notes__top"><h2>Recent notifications</h2>
        <div class="ac-filter" role="group" aria-label="Filter"><button class="is-on" type="button" data-f="all">All</button><button type="button" data-f="failed">Needs attention</button></div></div>
      <ul class="ac-nlist">
<?php foreach ($ev['notifications'] as $n): [$dl, $dt] = $deliv[$n[5]]; ?>
        <li data-d="<?= nu_e($n[5]) ?>"><span class="ac-nlist__ic"><?= nu_icon($nIcon[$n[0]] ?? 'bell') ?></span>
          <div><b><?= nu_e($n[1]) ?></b><p><?= nu_e($n[2]) ?></p><small><?= nu_e($n[3]) ?> · <?= nu_e($n[4]) ?></small></div>
          <span class="chip chip--<?= $dt ?>"><?= nu_e($dl) ?></span></li>
<?php endforeach; ?>
      </ul>
      <?= nu_sample_tag('Sample notifications') ?>
    </section>
<?php endif; ?>
  </div>
  <aside class="cp-rules ac-rules">
<?php if ($tab === 'activity'): ?>
    <h3>Events are auditable</h3><p>Each event records the actor, timestamp, booking, event type and any before / after value. Provider events come from actual actions.</p>
    <h3>Photo proof</h3><p>The contractor must upload at least one before and one after photo before the completion report can be submitted.</p>
    <h3>Approve to release payment</h3><p>Payment stays held until you approve the work. Reporting a problem keeps it on hold while Nuahn reviews.</p>
    <h3>Private by role</h3><p>You see your records. The contractor sees the job details they need. Admins handle cases and corrections with an audit log.</p>
<?php else: ?>
    <h3>One event, one notice</h3><p>Every notification comes from a single job event with an ID, so retries never double-send.</p>
    <h3>Delivery state</h3><p>Pending, delivered, read or failed is shown per channel. Failed notices retry and fall back to in-app.</p>
    <h3>Events vs messages</h3><p>Notifications describe what happened. Conversations with your contractor stay in <a href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a>.</p>
<?php endif; ?>
  </aside>
</main>
<script>
(function () {
  document.querySelectorAll('[data-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = document.getElementById(b.dataset.toggle), open = p.hidden;
      document.querySelectorAll('.ac-panel').forEach(function (x) { x.hidden = true; });
      document.querySelectorAll('[data-toggle]').forEach(function (x) { x.setAttribute('aria-expanded', 'false'); });
      p.hidden = !open; b.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  var r = document.querySelector('[data-rate]');
  if (r) r.addEventListener('submit', function (e) { e.preventDefault(); r.querySelector('.ac-ok').hidden = false; r.querySelector('button').disabled = true; });
  var i = document.querySelector('[data-issue]');
  if (i) i.addEventListener('submit', function (e) {
    e.preventDefault(); i.hidden = true;
    var c = document.querySelector('[data-case]'); c.classList.add('is-open');
    c.querySelector('[data-case-t]').textContent = 'CASE-311 · Open · Booking marked Disputed · reply within 1 business day (preview)';
  });
  document.querySelectorAll('[data-f]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-f]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
      document.querySelectorAll('.ac-nlist li').forEach(function (li) { li.hidden = b.dataset.f === 'failed' && li.dataset.d !== 'failed' && li.dataset.d !== 'pending'; });
    });
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
