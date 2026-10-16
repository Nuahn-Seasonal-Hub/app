<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/change_appointment.php: "Change your appointment" (doc2 p12). Same flow for every service.
// Keep the old slot until the new one is reserved and confirmed; explain the impact (old / new window, team,
// fees, this visit vs future plan visits). State-sensitive (doc2 p13): before cutoff + before departure a free
// slot confirms; once the team is travelling it becomes "Request change" for dispatch review.
// UI ONLY: availability is SAMPLE DATA. Sir Paul: GET /api/bookings/{id}/availability?week=, POST /api/bookings/{id}/reschedule
// {date, window, apply_to: this|future} -> {state: confirmed|pending_dispatch|declined}. Commit atomically (doc2 p22).
require_once("../config/init.php");
requireLogin('client');

$bookings = nu_fixture('bookings'); $services = nu_fixture('services');
$id = (string) ($_GET['id'] ?? 'MECH-1042');
$bk = null; foreach ($bookings as $b) { if ($b['id'] === $id) { $bk = $b; } }
$visits = nu_fixture('visits');
if (!$bk) { foreach ($visits as $s => $v) { if ($v['id'] === $id) { $bk = ['id' => $id, 'service' => $s, 'window' => $v['window'], 'team' => $v['team'], 'when' => $v['date'], 'status' => $v['status']]; } } }
if (!$bk) { $bk = $bookings[3]; $id = $bk['id']; }
$sv = $services[$bk['service']];
$reminder = !empty($_GET['reminder']);
// Demo of the state rule: ?state=on_route shows the dispatch-review variant.
$onRoute = ($_GET['state'] ?? '') === 'on_route' || in_array($bk['status'], ['on_the_way', 'arrived'], true);
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$result = $posted ? (string) ($_POST['result'] ?? ($onRoute ? 'pending' : 'confirmed')) : '';
$days = [['Mon', 'available'], ['Tue', 'current'], ['Wed', 'selected'], ['Thu', 'available'], ['Fri', 'available'], ['Sat', 'available'], ['Sun', 'available']];
$wins = [['9–11 AM', 'available'], ['1–3 PM', 'selected'], ['3–5 PM', 'available'], ['11 AM–1 PM', 'full']];
$title = $sv['name'] . ($bk['service'] === 'mobile-mechanics' ? ' · Brake Pad Replacement' : '');
include_once("../includes/header.php");
?>
<main class="ch" id="change">
  <div class="ch-head">
    <h1>Change your appointment</h1>
    <p>Choose a new available window for any booked service.</p>
  </div>
<?php if ($posted): ?>
  <section class="ch-result ch-result--<?= nu_e($result) ?>" role="status">
<?php if ($result === 'confirmed'): ?>
    <?= nu_icon('check-circle-fill') ?><div><h2>New window confirmed</h2><p>Your <?= nu_e($sv['name']) ?> visit is now <b>Wednesday, 1–3 PM</b>. Your old slot (Tuesday, 10 AM–12 PM) has been released. You and <?= nu_e($bk['team']) ?> have been notified, and the change is recorded in <a href="<?= nu_e(nu_url('public/activity.php')) ?>">Activity</a>.</p></div>
<?php elseif ($result === 'pending'): ?>
    <?= nu_icon('hourglass-split') ?><div><h2>Change requested · pending dispatch</h2><p>Your team is already travelling, so dispatch reviews this request. <b>Your original window stays booked</b> until dispatch confirms. We’ll notify you either way.</p></div>
<?php else: ?>
    <?= nu_icon('x-circle') ?><div><h2>That window was just taken</h2><p>Capacity changed while you were choosing. <b>Your original appointment is unchanged.</b> Please pick another available window.</p></div>
<?php endif; ?>
    <div class="row-flex"><a class="btn btn-primary btn-sm" href="<?= nu_e(nu_url('public/bookings.php')) ?>">My bookings</a><a class="btn btn-outline btn-sm" href="?id=<?= nu_e($id) ?>">Back</a></div>
  </section>
<?php endif; ?>
  <div class="ch-grid">
    <form class="ch-main" method="post" action="?id=<?= nu_e(rawurlencode($id)) ?><?= $onRoute ? '&amp;state=on_route' : '' ?>" data-change>
      <section class="sv-card ch-bk">
        <span class="ch-bk__ic"><?= nu_icon($sv['icon']) ?></span>
        <div><b><?= nu_e($title) ?></b><span>Current: <?= nu_e($bk['service'] === 'mobile-mechanics' ? 'Tuesday, 10 AM–12 PM' : $bk['when'] . ', ' . $bk['window']) ?></span></div>
        <span class="ch-bk__m">Booking #<?= nu_e(str_replace('MECH', 'NSH', $id)) ?></span>
        <span class="ch-bk__m">Assigned: <?= nu_e($bk['team']) ?></span>
      </section>
<?php if ($onRoute): ?>
      <p class="ch-warn"><?= nu_icon('truck') ?> Your team is on the way. Changes now go to dispatch as a request; nothing changes until they confirm.</p>
<?php endif; ?>
<?php if ($reminder): ?>
      <p class="rv-flag"><?= nu_icon('bell') ?> Changing the date from your monthly reminder. This picks a window; it doesn’t book or charge until you confirm.</p>
<?php endif; ?>
      <section class="sv-card ch-pick">
        <div class="ch-col">
          <h2 class="ch-h"><span class="ch-n">1</span> Select a new date</h2>
          <div class="ch-days" role="radiogroup" aria-label="Day">
<?php foreach ($days as $d): ?>
            <label class="ch-day ch-day--<?= $d[1] ?>"><input type="radio" name="day" value="<?= $d[0] ?>"<?= $d[1] === 'selected' ? ' checked' : '' ?><?= $d[1] === 'current' ? ' disabled' : '' ?>><span><?= $d[0] ?><i aria-hidden="true"></i><?= $d[1] === 'current' ? '<small>Current</small>' : '' ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
        <div class="ch-col">
          <h2 class="ch-h"><span class="ch-n">2</span> <span>Choose an available arrival window<small data-day-label>Wednesday availability</small></span></h2>
          <div class="ch-wins" role="radiogroup" aria-label="Arrival window">
<?php foreach ($wins as $w): ?>
            <label class="ch-win ch-win--<?= $w[1] ?>"><input type="radio" name="window" value="<?= $w[0] ?>"<?= $w[1] === 'selected' ? ' checked' : '' ?><?= $w[1] === 'full' ? ' disabled' : '' ?>><i class="ch-win__r" aria-hidden="true"></i><span><?= nu_e($w[0]) ?></span><em><?= $w[1] === 'full' ? 'Full' : 'Available' ?></em></label>
<?php endforeach; ?>
          </div>
        </div>
        <div class="ch-cmp">
          <div><?= nu_icon('calendar3') ?><span>Old window:<b>Tuesday, 10 AM–12 PM</b></span></div>
          <?= nu_icon('arrow-right', 'ch-cmp__arrow') ?>
          <div class="is-new"><?= nu_icon('calendar-check') ?><span>New window:<b data-new-win>Wednesday, 1–3 PM</b></span></div>
        </div>
        <div class="ch-apply">
          <fieldset class="fset"><legend class="label">Apply this change to:</legend>
            <label class="radio"><input type="radio" name="apply" value="this" checked><span>This visit only<small>Only change this booking.</small></span></label>
          </fieldset>
          <div class="ch-apply__prem"><p class="ch-premtag"><?= nu_icon('gem') ?> Premium setting</p><label class="radio"><input type="radio" name="apply" value="future"><span>Future plan visits<small>Apply this change to upcoming visits in your plan.</small></span></label></div>
          <p class="ch-info"><?= nu_icon('info-circle') ?> Your current booking stays in place until the change is confirmed.</p>
        </div>
      </section>
      <div class="ch-acts">
        <input type="hidden" name="result" value="<?= $onRoute ? 'pending' : 'confirmed' ?>" data-result>
        <button class="btn btn-primary btn-lg" type="submit"><?= $onRoute ? 'Request change' : 'Confirm new window' ?></button>
        <a class="btn btn-outline btn-lg" href="<?= nu_e(nu_url('public/bookings.php')) ?>">Keep current appointment</a>
        <p class="ch-notify"><?= nu_icon('envelope') ?><span><b>Notification preview</b>Customer and assigned team receive the updated schedule.</span></p>
      </div>
      <p class="ch-demo">Preview states: <a href="?id=<?= nu_e($id) ?>">Before cutoff (auto-confirm)</a> · <a href="?id=<?= nu_e($id) ?>&amp;state=on_route">Team on route (dispatch review)</a> · <button type="submit" class="linkbtn" data-declined>Slot taken (declined)</button> <?= nu_sample_tag('Sample availability') ?></p>
    </form>
    <aside class="ch-side">
      <section class="sv-card ch-check">
        <h2 class="sv-card__h"><?= nu_icon('shield-check') ?> Check before confirming</h2>
        <p class="sv-tip"><?= nu_icon('file-text') ?><span>Any fee or provider change appears here.<b class="ch-fee">No fee · same team (<?= nu_e($bk['team']) ?>)</b></span></p>
        <p class="sv-tip"><?= nu_icon('people') ?><span>Available slots depend on team capacity.</span></p>
      </section>
      <div class="cp-rules">
        <h3>Across all services</h3><p>The same Change appointment flow applies to mechanics, car washing, bins, lawn mowing and snow visits.</p>
        <h3>Keep the old slot</h3><p>Keep the existing booking until the new slot is reserved and confirmed. If capacity changes, ask for another slot.</p>
        <h3>Explain the impact</h3><p>Show old / new window, changed team, fees if any, and whether this visit or future plan preferences are affected.</p>
        <h3>After confirmation</h3><p>Notify customer and provider, release the old slot, update route / reminders and record the change in Activity.</p>
      </div>
    </aside>
  </div>
</main>
<script>
(function () {
  var f = document.querySelector('[data-change]'); if (!f) return;
  var names = { Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thursday', Fri: 'Friday', Sat: 'Saturday', Sun: 'Sunday' };
  function upd() {
    var d = f.querySelector('input[name=day]:checked'), w = f.querySelector('input[name=window]:checked');
    f.querySelector('[data-day-label]').textContent = names[d.value] + ' availability';
    f.querySelector('[data-new-win]').textContent = names[d.value] + ', ' + (w ? w.value : 'choose a window');
  }
  f.addEventListener('change', upd);
  f.querySelector('[data-declined]').addEventListener('click', function () { f.querySelector('[data-result]').value = 'declined'; });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
