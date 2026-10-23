<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/care_plans.php: "Your care plans" (doc2 p10): monthly provider reminder with Confirm a visit /
// Change date / Skip this month, plan settings (cadence, preferred time, visit mode) and what each action means.
// UI ONLY: fixtures/care_plans.php. Reminders never create jobs or charges; auto-scheduled or storm-triggered
// visits need a separately agreed mode. Sir Paul: see docs/UI-DATA-HOOKUP.md "Care plans".
require_once("../config/init.php");
requireLogin('client');
$cp = nu_fixture('care_plans');
$services = nu_fixture('services');
$rem = $cp['reminders'][0];
$edit = ($_GET['edit'] ?? '') !== '';
include_once("../includes/header.php");
?>
<main class="cp" id="care">
  <div class="cp-head">
    <h1>Your care plans</h1>
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= nu_e(nu_url('public/client_home.php')) ?>">Nuahn</a> / <a href="<?= nu_e(nu_url('public/bookings.php')) ?>">My Bookings</a> / <a href="<?= nu_e(nu_url('public/activity.php')) ?>">Activity</a> / <a href="<?= nu_e(nu_url('public/messages.php')) ?>">Messages</a></nav>
  </div>
  <div class="cp-grid">
    <div class="cp-main">
      <section class="cp-box cp-rem" data-reminder>
        <div class="cp-rem__top">
          <h2><?= nu_e($rem['title']) ?></h2>
          <span class="chip chip--blue"><?= nu_e($rem['delivery']) ?></span>
        </div>
        <p>Provider: <?= nu_e($rem['provider']) ?><br><?= nu_e($rem['text']) ?></p>
        <p class="cp-meta"><?= nu_icon('envelope-check') ?> Sent <?= nu_e($rem['sent']) ?> by scheduled template · replies go to your <a href="<?= nu_e(nu_url('public/chat.php')) ?>?thread=t4">message thread</a></p>
        <div class="cp-acts" data-reminder-acts>
          <a class="btn btn-primary" href="<?= nu_e(nu_url('public/book.php')) ?>?service=<?= nu_e($rem['service']) ?>&amp;from=reminder">Confirm a visit</a>
          <a class="btn btn-soft" href="<?= nu_e(nu_url('public/change_appointment.php')) ?>?id=MECH-1042&amp;reminder=1">Change date</a>
          <button class="btn btn-soft" type="button" data-skip>Skip this month</button>
        </div>
        <p class="sv-prem__done" data-skipped hidden><?= nu_icon('check-circle-fill') ?> Skipped for this cycle only. Your plan stays active. <button type="button" class="linkbtn" data-unskip>Undo</button></p>
      </section>

      <section class="cp-box" id="settings">
        <h2>Plan settings</h2>
<?php if (!$edit): $p0 = $cp['plans'][0]; ?>
        <p>Reminder cadence: <?= nu_e($p0['cadence']) ?> | Preferred time: <?= nu_e($p0['time']) ?><br>Visit mode: <?= nu_e($p0['mode']) ?><br>
        Actions: <a href="?edit=1#settings">Edit preferences</a> | <button class="linkbtn" type="button" data-pause>Pause plan</button> | <button class="linkbtn" type="button" data-cancel>Cancel plan</button></p>
        <p class="cp-confirm" data-pause-box hidden><?= nu_icon('pause-circle') ?><span>Pause from <input class="input input-sm" type="date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" aria-label="Pause from date">. Future reminders stop from this date; existing visits stay visible for separate action. <button class="btn btn-primary btn-sm" type="button" data-done="Plan paused (preview)">Pause plan</button></span></p>
        <p class="cp-confirm cp-confirm--danger" data-cancel-box hidden><?= nu_icon('exclamation-triangle') ?><span>Cancelling stops reminders under the plan terms. Booked visits are not cancelled automatically. <button class="btn btn-danger btn-sm" type="button" data-done="Plan cancelled (preview)">Cancel plan</button></span></p>
<?php else: ?>
        <form class="cp-form" method="get" action="#settings">
          <div class="fld-row">
            <label class="fld"><span class="label">Reminder cadence</span><select class="input"><option selected>Monthly</option><option>Every 2 months</option><option>Seasonal</option></select></label>
            <label class="fld"><span class="label">Preferred time</span><select class="input"><option>Morning</option><option selected>Afternoon</option><option>Evening</option></select></label>
          </div>
          <fieldset class="fset fld"><legend class="label">Visit mode</legend><div class="radios radios--stack">
            <label class="radio"><input type="radio" name="mode" checked><span>Ask me to confirm each visit</span></label>
            <label class="radio"><input type="radio" name="mode"><span>Auto-schedule visits (requires a separately agreed mode and terms)</span></label>
          </div></fieldset>
          <label class="fld"><span class="label">Reminder channels</span><span class="checks checks--inline"><label class="check"><input type="checkbox" checked><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">Email</span></label><label class="check"><input type="checkbox" checked><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">Push</span></label><label class="check"><input type="checkbox"><span class="check__box"><?= nu_icon('check-lg') ?></span><span class="check__t">SMS</span></label></span></label>
          <div class="row-flex"><a class="btn btn-primary" href="?saved=1#settings">Save preferences</a><a class="btn btn-ghost" href="?#settings">Cancel</a></div>
        </form>
<?php endif; ?>
<?php if (!empty($_GET['saved'])): ?>
        <div class="alert alert-success"><?= nu_icon('check-circle-fill') ?> Preferences saved (preview only, nothing stored).</div>
<?php endif; ?>
      </section>

      <section class="cp-box">
        <h2>What each action means</h2>
        <p>Confirm opens available slots and price / scope review. Skip changes this cycle only. Pause stops future reminders or generation from the chosen date. Existing visits remain visible for separate action.</p>
      </section>

      <section class="sv-card">
        <h2 class="rv-h">All plans</h2>
        <div class="cp-plans">
<?php foreach ($cp['plans'] as $p): $o = $services[$p['service']]; ?>
          <article class="cp-plan">
            <span class="cp-plan__ic"><?= nu_icon($o['icon']) ?></span>
            <div class="cp-plan__b"><b><?= nu_e($p['name']) ?></b><span><?= nu_e($o['name']) ?> · <?= nu_e($p['provider']) ?></span>
              <dl><dt>Cadence</dt><dd><?= nu_e($p['cadence']) ?></dd><dt>Visit mode</dt><dd><?= nu_e($p['mode']) ?></dd><dt>Plan fee</dt><dd><?= nu_e($p['fee']) ?></dd><dt>Visits</dt><dd><?= nu_e($p['visit']) ?></dd></dl>
            </div>
            <div class="cp-plan__s"><?= $p['status'] === 'active' ? '<span class="chip chip--success">Active</span>' : '<span class="chip chip--warning">Paused</span>' ?><small><?= nu_e($p['next']) ?></small></div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

      <section class="sv-card">
        <h2 class="rv-h">Reminder history</h2>
        <ul class="cp-hist">
<?php foreach ($cp['reminders'] as $r): ?>
          <li><span><?= nu_icon($services[$r['service']]['icon']) ?></span><div><b><?= nu_e($r['title']) ?></b><small><?= nu_e($r['provider']) ?> · sent <?= nu_e($r['sent']) ?> · <?= nu_e($r['delivery']) ?></small></div><?= nu_booking_chip($r['state'] === 'due' ? 'reminder_due' : $r['state']) ?></li>
<?php endforeach; ?>
        </ul>
      </section>
    </div>
    <aside class="cp-rules">
      <h3>Plan activation</h3><p>Show service, cadence, included scope, optional plan fee, per-visit charges, billing terms and cancellation rules before sign-up.</p>
      <h3>Monthly outreach</h3><p>Provider reminder can be sent by a scheduled template. Replies enter the shared message thread. Display sender and delivery state.</p>
      <h3>Automation opt-in</h3><p>Reminders do not create jobs or charges. Auto-scheduled or storm-triggered visits need a separately agreed mode.</p>
      <h3>Service-aware care</h3><p>Premium mechanic reminders are check-ins; they never imply monthly brake repairs or oil changes are needed.</p>
      <p><?= nu_sample_tag('Sample plans') ?></p>
    </aside>
  </div>
</main>
<script>
(function () {
  document.querySelectorAll('[data-reminder]').forEach(function (r) {
    r.addEventListener('click', function (e) {
      var acts = r.querySelector('[data-reminder-acts]'), done = r.querySelector('[data-skipped]');
      if (e.target.closest('[data-skip]')) { acts.hidden = true; done.hidden = false; }
      if (e.target.closest('[data-unskip]')) { acts.hidden = false; done.hidden = true; }
    });
  });
  var pb = document.querySelector('[data-pause-box]'), cb = document.querySelector('[data-cancel-box]');
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-pause]')) { pb.hidden = !pb.hidden; cb.hidden = true; }
    if (e.target.closest('[data-cancel]')) { cb.hidden = !cb.hidden; pb.hidden = true; }
    var d = e.target.closest('[data-done]'); if (d) { d.parentNode.textContent = d.dataset.done + '. Existing visits are unchanged.'; }
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
