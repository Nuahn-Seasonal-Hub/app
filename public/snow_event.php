<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/snow_event.php: customer view of a snow event + "come later" reschedule (doc2 p4, p13).
// Five-step flow: forecast notice -> qualifying event -> route published -> customer asks to come later
// -> change confirmed or declined. A forecast never creates a guaranteed visit; the original booking stays
// while dispatch checks the route. UI ONLY: event, route and plan terms are SAMPLE DATA.
// Sir Paul: GET /api/weather/events/current?address_id=, GET /api/bookings/{id}, POST /api/bookings/{id}/defer
// {option: later_today|another_day, date?, window?} -> {state: pending|confirmed|declined, alternatives[]}.
// Preview states: ?state=forecast|published|pending|confirmed|declined, ?weather=unavailable, ?as=basic.
require_once("../config/init.php");
requireLogin('client');

$v = nu_fixture('visits')['snow-removal'];
$state = (string) ($_GET['state'] ?? 'published');
if (!in_array($state, ['forecast', 'published', 'pending', 'confirmed', 'declined'], true)) $state = 'published';
if ($_SERVER['REQUEST_METHOD'] === 'POST') $state = 'pending';
$basic = ($_GET['as'] ?? '') === 'basic';
$wxDown = ($_GET['weather'] ?? '') === 'unavailable';
$at = ['forecast' => 0, 'published' => 2, 'pending' => 3, 'confirmed' => 4, 'declined' => 4][$state];
$flow = [['Forecast notice', '6:00 AM'], ['Qualifying event', '7:20 AM'], ['Route published', '8:05 AM'], ['Come later requested', $state === 'pending' ? 'Now' : ''], [$state === 'declined' ? 'Change declined' : 'Change confirmed', '']];
if ($at < 3) { $flow[3][1] = ''; }
$terms = [
  ['Trigger', '5 cm or more, confirmed by dispatch'], ['Included areas', 'Driveway, front walkway, steps'],
  ['Repeat passes', 'One extra pass if 10 cm+ more falls within 24 h'], ['Response target', 'Within 8 h of event confirmation'],
];
include_once("../includes/header.php");
?>
<main class="se" id="snow">
  <p class="crumbs"><a href="<?= nu_e(nu_book_url('snow-removal')) ?>">Winter Snow Removal</a> / Snow event</p>
  <div class="se-head">
    <div>
      <h1><?= $state === 'forecast' ? 'Snow in the forecast' : 'Snow event in your area' ?></h1>
      <p><?= $state === 'forecast' ? 'Heads-up only. A forecast doesn’t create a visit; dispatch confirms qualifying events.' : 'Clear outcomes for you while dispatch keeps the route workable.' ?></p>
    </div>
    <span class="se-id"><?= nu_icon('snow') ?> Event <b>EV-EDM-1016</b> <?= $state === 'forecast' ? nu_booking_chip('queued') : nu_booking_chip('confirmed') ?></span>
  </div>

<?php if ($wxDown): ?>
  <p class="se-wx se-wx--down" role="status"><?= nu_icon('cloud-slash') ?><span><b>Weather unavailable</b> We can’t load the forecast right now. Dispatch is still operating and your booking is unchanged. Next update by 10:30 AM.</span></p>
<?php else: ?>
  <p class="se-wx" role="status"><?= nu_icon('cloud-snow') ?><span><b><?= nu_e($v['snowfall']['title']) ?></b> 12 cm expected by noon · −8°C · <?= nu_e($v['snowfall']['text']) ?> Updated <?= nu_e($v['snowfall']['updated']) ?>, next update 10:30 AM.</span></p>
<?php endif; ?>

  <section class="sv-card se-flow" aria-label="Snow-event progress">
    <?= nu_steps($flow, $at) ?>
  </section>

  <div class="se-grid">
    <div class="se-main">
<?php if ($state === 'forecast'): ?>
      <section class="sv-card">
        <h2 class="sv-card__h"><?= nu_icon('binoculars') ?> What happens next</h2>
        <p class="sv-p">If dispatch confirms a qualifying event (5 cm or more), <?= $basic ? 'you can request a one-off visit from this page.' : 'your Premium plan gets one visit for this event, and you’ll see your team and arrival window here.' ?></p>
        <p class="sv-p muted">No visit has been created yet. Nothing is charged for a forecast.</p>
        <?php if ($basic): ?><button class="btn btn-outline" type="button" disabled>Request a one-off visit (after confirmation)</button><?php endif; ?>
      </section>
<?php else: ?>
      <section class="sv-card se-visit">
        <div class="se-visit__top">
          <h2 class="sv-card__h"><?= nu_icon('truck') ?> Your snow visit</h2>
          <?= nu_booking_chip($state === 'confirmed' ? 'confirmed' : ($state === 'pending' ? 'change_requested' : 'assigned')) ?>
        </div>
        <dl class="se-facts">
          <div><dt>Booking</dt><dd>#<?= nu_e($v['id']) ?></dd></div>
          <div><dt>Team</dt><dd><?= nu_e($v['team']) ?> · <?= nu_e($v['team_note']) ?></dd></div>
          <div><dt>Arrival window</dt><dd><?= $state === 'confirmed' ? '<s>' . nu_e($v['later']['current']) . '</s> <b>' . nu_e($v['later']['proposed']) . '</b>' : nu_e($v['later']['current']) ?></dd></div>
          <div><dt>Areas</dt><dd><?= nu_e($v['areas']) ?></dd></div>
          <div><dt>Plan</dt><dd><?= $basic ? 'Basic · one-off request' : 'Premium · auto-service plan' ?></dd></div>
          <div><dt>Event</dt><dd>EV-EDM-1016</dd></div>
        </dl>
        <?= nu_mini_map(1, 'Map preview of Team A’s snow route') ?>
      </section>

<?php if ($state === 'published'): ?>
      <form class="sv-card se-later" method="post" data-later>
        <h2 class="sv-card__h"><?= nu_icon('clock-history') ?> Busy? Come later</h2>
        <p class="sv-p">Ask for later today or another available day. <b>Your original booking stays in place</b> while dispatch checks the route and response commitments.</p>
        <div class="radios se-opts">
          <label class="se-opt"><input type="radio" name="option" value="later_today" checked><span><b>Later today</b><small>Next available: 2:00 PM – 4:00 PM</small></span></label>
          <label class="se-opt"><input type="radio" name="option" value="another_day"><span><b>Another day</b><small>Pick from available windows</small></span></label>
        </div>
        <div class="se-day" data-day hidden>
          <label class="label" for="se-date">Available day</label>
          <select class="input" id="se-date" name="date"><option>Tomorrow, Sat Oct 17 · 8–10 AM</option><option>Tomorrow, Sat Oct 17 · 1–3 PM</option><option>Sun Oct 18 · 9–11 AM</option></select>
          <p class="fld-note"><?= nu_icon('info-circle') ?> The plan’s response target still applies. Moving past it may need your agreement on the plan terms.</p>
        </div>
        <label class="label" for="se-note">Note for dispatch (optional)</label>
        <textarea class="input" id="se-note" name="note" rows="2" placeholder="e.g. Car leaves the driveway at 1 PM"></textarea>
        <div class="se-acts"><button class="btn btn-primary" type="submit">Request later visit</button><a class="btn btn-outline" href="<?= nu_e(nu_url('public/track.php')) ?>">Keep current window</a></div>
      </form>
<?php elseif ($state === 'pending'): ?>
      <section class="sv-card se-out se-out--pending" role="status">
        <?= nu_icon('hourglass-split') ?><div><h2>Request sent · dispatch is checking the route</h2>
        <p>Requested: <b><?= nu_e($v['later']['proposed']) ?></b>. Until dispatch confirms, your original window (<?= nu_e($v['later']['current']) ?>) stays booked and Team A may still arrive then.</p>
        <p class="muted">Usually answered within 30 minutes. You’ll get a notification and a message in this booking’s thread.</p></div>
      </section>
<?php elseif ($state === 'confirmed'): ?>
      <section class="sv-card se-out se-out--ok" role="status">
        <?= nu_icon('check-circle-fill') ?><div><h2>New window confirmed</h2>
        <p>Team A will now come <b><?= nu_e($v['later']['proposed']) ?></b>. Your job and the route have been updated. Other customers only see their own new windows.</p>
        <div class="se-acts"><a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/track.php')) ?>">Track visit</a><a class="btn btn-outline btn-sm" href="<?= nu_e(nu_url('public/messages.php')) ?>">Message team</a></div></div>
      </section>
<?php else: ?>
      <section class="sv-card se-out se-out--no" role="status">
        <?= nu_icon('x-circle') ?><div><h2>We couldn’t move this visit</h2>
        <p>Reason: the 2–4 PM route is full and later passes would miss the plan’s 8-hour response target. <b>Your existing visit stays</b>: <?= nu_e($v['later']['current']) ?>.</p>
        <p class="label">Alternatives</p>
        <div class="se-alts"><button class="btn btn-outline btn-sm" type="button">Tomorrow 8–10 AM (extra pass)</button><button class="btn btn-outline btn-sm" type="button">Leave access notes instead</button><a class="btn btn-soft btn-sm" href="<?= nu_e(nu_url('public/messages.php')) ?>">Message dispatch</a></div></div>
      </section>
<?php endif; ?>
<?php endif; ?>
      <p class="ch-demo">Preview states:
        <?php foreach (['forecast' => 'Forecast', 'published' => 'Route published', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'declined' => 'Declined'] as $k => $l): ?><a href="?state=<?= $k ?>"<?= $k === $state ? ' aria-current="page"' : '' ?>><?= $l ?></a> · <?php endforeach; ?>
        <a href="?state=<?= nu_e($state) ?>&amp;weather=unavailable">Weather unavailable</a> · <a href="?state=forecast&amp;as=basic">Basic customer</a>
        <?= nu_sample_tag('Sample event') ?></p>
    </div>
    <aside class="se-side">
      <section class="sv-card">
        <h2 class="sv-card__h"><?= nu_icon('file-text') ?> <?= $basic ? 'One-off snow visits' : 'Your plan terms' ?></h2>
<?php if ($basic): ?>
        <p class="sv-p">Basic customers can request a one-off slot once dispatch publishes the route. Price and window are shown before you confirm.</p>
        <a class="btn btn-soft btn-sm" href="<?= nu_e(nu_url('public/plans.php')) ?>">Compare plans</a>
<?php else: ?>
        <dl class="se-terms"><?php foreach ($terms as $t): ?><div><dt><?= nu_e($t[0]) ?></dt><dd><?= nu_e($t[1]) ?></dd></div><?php endforeach; ?></dl>
        <p class="fld-note"><?= nu_icon('shield-check') ?> A second pass is an explicit extra-visit rule, never a duplicate alert. Requests to come later can’t silently remove these commitments.</p>
<?php endif; ?>
      </section>
      <div class="cp-rules">
        <h3>Forecast ≠ visit</h3><p>A forecast shows a heads-up. Only a confirmed qualifying event creates one job per intended visit.</p>
        <h3>State-sensitive controls</h3><p>Before cutoff and departure, free slots confirm automatically. On route it becomes a request for dispatch. Once work starts, contact support.</p>
        <h3>Rain, cold and access</h3><p>Each service has its own weather rules: frozen taps for washing, wet ground for mowing, snow for vehicle access. Each delay shows a reason, the next update time and reschedule controls.</p>
      </div>
    </aside>
  </div>
</main>
<script>
(function () {
  var f = document.querySelector('[data-later]'); if (!f) return;
  var d = f.querySelector('[data-day]');
  f.addEventListener('change', function () { d.hidden = f.querySelector('input[name=option]:checked').value !== 'another_day'; });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
