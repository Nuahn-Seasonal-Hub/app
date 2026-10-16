<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/plans.php: "Basic or Premium. Your choice." (doc2 p9). Booking mode and ongoing reminders are separate
// from the work package. Premium never implies unlimited work, discounts, priority routing or a recurring charge.
// UI ONLY. Sir Paul: GET /api/plans (terms per service: cadence, included scope, plan fee, per-visit charges, cancellation).
require_once("../config/init.php");
$services = nu_fixture('services');
$logged = isset($_SESSION['user_id']) && isRole('client');
$rem = nu_fixture('care_plans')['reminders'][0];
include_once("../includes/header.php");
?>
<main class="pl" id="plans">
  <div class="pl-head">
    <h1>Basic or Premium. Your choice.</h1>
    <p>Available across Nuahn services. Select a service and see its plan details.</p>
    <label class="pl-svc"><span class="sr-only">Service</span><select class="input" data-plan-svc>
<?php foreach ($services as $s => $o): ?>
      <option value="<?= nu_e($s) ?>"<?= $s === 'car-wash' ? ' selected' : '' ?>><?= nu_e($o['name']) ?></option>
<?php endforeach; ?>
    </select></label>
  </div>
  <div class="pl-grid">
    <section class="pl-card">
      <span class="pl-ic"><?= nu_icon('calendar3') ?></span>
      <p class="pl-tag">Basic</p>
      <h2>Book whenever you need it</h2>
      <ul class="pl-list">
        <li><?= nu_icon('check-lg') ?>One service visit at a time</li>
        <li><?= nu_icon('check-lg') ?>Pick an available date and window</li>
        <li><?= nu_icon('check-lg') ?>Track your team and job progress</li>
        <li><?= nu_icon('check-lg') ?>Message, reschedule and view receipts</li>
        <li><?= nu_icon('check-lg') ?>No ongoing care plan</li>
      </ul>
      <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(nu_book_url('car-wash')) ?>" data-plan-link="basic">Book a one-time service <?= nu_icon('arrow-right') ?></a>
    </section>
    <section class="pl-card pl-card--prem">
      <span class="pl-ic"><?= nu_icon('arrow-repeat') ?></span>
      <p class="pl-tag">Premium</p>
      <h2>Stay on top of regular care</h2>
      <ul class="pl-list">
        <li><?= nu_icon('check-lg') ?>Monthly reminders from your provider</li>
        <li><?= nu_icon('check-lg') ?>Save service and location preferences</li>
        <li><?= nu_icon('check-lg') ?>Confirm, change or skip the next visit</li>
        <li><?= nu_icon('check-lg') ?>Manage an ongoing care plan</li>
        <li><?= nu_icon('check-lg') ?>Track every visit and message your team</li>
      </ul>
      <a class="btn btn-primary btn-lg btn-block" href="<?= nu_e(nu_book_url('car-wash')) ?>&amp;plan=premium" data-plan-link="premium">Set up a care plan <?= nu_icon('arrow-right') ?></a>
    </section>
  </div>
<?php if ($logged): ?>
  <section class="pl-rem" data-reminder>
    <span class="pl-ic"><?= nu_icon('car-front') ?></span>
    <div class="pl-rem__t"><b>Your monthly car-care reminder</b><span>Would you like to book this month’s car wash?</span></div>
    <div class="pl-rem__a" data-reminder-acts>
      <a class="btn btn-primary" href="<?= nu_e(nu_url('public/book.php')) ?>?service=car-wash&amp;from=reminder">Confirm a visit</a>
      <a class="btn btn-outline" href="<?= nu_e(nu_url('public/change_appointment.php')) ?>?id=WASH-1048&amp;reminder=1">Change date</a>
      <button class="btn btn-outline" type="button" data-skip>Skip this month</button>
    </div>
    <p class="sv-prem__done" data-skipped hidden><?= nu_icon('check-circle-fill') ?> Skipped for this month. <button type="button" class="linkbtn" data-unskip>Undo</button></p>
  </section>
<?php endif; ?>
  <div class="pl-fine">
    <span>A reminder is not a booking or charge. Automatic visits require separate opt-in.</span>
    <span>Service schedule and payment terms are shown before plan activation.</span>
  </div>

  <div class="pl-rules" id="terms">
    <div><h3>Basic = on demand</h3><p>Book a service whenever needed. No ongoing care plan is required.</p></div>
    <div><h3>Premium = ongoing care</h3><p>Sign up for a service plan; the provider reminds you each month and helps arrange the next visit.</p></div>
    <div><h3>Both retain control</h3><p>Tracking, messages, appointment changes, invoices and support are available in both modes.</p></div>
    <div><h3>No hidden assumptions</h3><p>Premium does not imply unlimited work, discounts, priority routing or a recurring charge. Plan terms say exactly what is included.</p></div>
  </div>

  <section class="sv-card pl-table">
    <h2 class="rv-h">What Premium means for each service</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Service</th><th>Basic</th><th>Premium</th></tr></thead>
      <tbody>
<?php foreach ($services as $s => $o): [$b, $p] = array_pad(explode(' / ', $o['plans'], 2), 2, ''); ?>
        <tr><td><a href="<?= nu_e(nu_book_url($s)) ?>"><?= nu_icon($o['icon']) ?> <?= nu_e($o['name']) ?></a></td><td><?= nu_e(ucfirst($b)) ?></td><td><?= nu_e(ucfirst($p)) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="sv-note"><?= nu_icon('info-circle') ?> Plan fee, per-visit charges, billing terms and cancellation rules are configured by Nuahn and shown before sign-up. <?= nu_sample_tag('Sample terms') ?></p>
  </section>
</main>
<script>
(function () {
  var sel = document.querySelector('[data-plan-svc]');
  if (sel) sel.addEventListener('change', function () {
    document.querySelectorAll('[data-plan-link]').forEach(function (a) { a.href = a.href.replace(/s=[^&]+/, 's=' + sel.value); });
  });
  document.querySelectorAll('[data-reminder]').forEach(function (r) {
    r.addEventListener('click', function (e) {
      var acts = r.querySelector('[data-reminder-acts]'), done = r.querySelector('[data-skipped]');
      if (e.target.closest('[data-skip]')) { acts.hidden = true; done.hidden = false; }
      if (e.target.closest('[data-unskip]')) { acts.hidden = false; done.hidden = true; }
    });
  });
})();
</script>
<?php include_once("../includes/footer.php"); ?>
